<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PurchaseService
{
    /**
     * Post a supplier invoice, update batch inventory, create stock movements, and record supplier payable.
     */
    public function postSupplierInvoice(array $data, User $user): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $user) {
            $supplier = Supplier::where('id', $data['supplier_id'])->lockForUpdate()->firstOrFail();

            // Duplicate supplier invoice check
            $existing = PurchaseInvoice::where('organization_id', $user->organization_id)
                ->where('supplier_id', $supplier->id)
                ->where('invoice_number', $data['invoice_number'])
                ->first();

            if ($existing) {
                throw new RuntimeException("Invoice '{$data['invoice_number']}' has already been posted for supplier '{$supplier->name}'.");
            }

            $warehouseId = $data['warehouse_id'] ?? $user->branch->warehouses()->first()->id;

            // Compute invoice totals
            $subtotal = 0;
            $discountTotal = 0;
            $taxableTotal = 0;
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
            $itemsToCreate = [];

            foreach ($data['items'] as $itemData) {
                $qty = (float)$itemData['quantity'];
                $freeQty = (float)($itemData['free_quantity'] ?? 0);
                $unitCost = (float)$itemData['unit_cost'];
                $discPercent = (float)($itemData['discount_percent'] ?? 0);
                $gstRate = (float)$itemData['gst_rate'];

                $gross = $qty * $unitCost;
                $discAmt = round(($gross * $discPercent) / 100, 2);
                $taxable = $gross - $discAmt;

                $taxAmt = round(($taxable * $gstRate) / 100, 2);
                $cgst = round($taxAmt / 2, 2);
                $sgst = round($taxAmt / 2, 2);
                $igst = 0; // Intra-state default, could check supplier vs branch state

                $lineTotal = $taxable + $taxAmt;

                $subtotal += $gross;
                $discountTotal += $discAmt;
                $taxableTotal += $taxable;
                $cgstTotal += $cgst;
                $sgstTotal += $sgst;
                $igstTotal += $igst;

                $itemsToCreate[] = [
                    'raw' => $itemData,
                    'quantity' => $qty,
                    'free_quantity' => $freeQty,
                    'total_physical_quantity' => $qty + $freeQty,
                    'unit_cost' => $unitCost,
                    'mrp' => (float)$itemData['mrp'],
                    'sale_price' => (float)($itemData['sale_price'] ?? $itemData['mrp']),
                    'wholesale_price' => (float)($itemData['wholesale_price'] ?? ($itemData['unit_cost'] * 1.1)),
                    'discount_percent' => $discPercent,
                    'discount_amount' => $discAmt,
                    'taxable_value' => $taxable,
                    'gst_rate' => $gstRate,
                    'cgst_amount' => $cgst,
                    'sgst_amount' => $sgst,
                    'igst_amount' => $igst,
                    'total_amount' => $lineTotal,
                ];
            }

            $additionalCharges = (float)($data['additional_charges'] ?? 0);
            $totalTax = $cgstTotal + $sgstTotal + $igstTotal;
            $netBeforeRound = $taxableTotal + $totalTax + $additionalCharges;
            $roundedNet = round($netBeforeRound);
            $roundOff = round($roundedNet - $netBeforeRound, 2);

            $dueDate = !empty($data['due_date'])
                ? $data['due_date']
                : Carbon::parse($data['invoice_date'])->addDays($supplier->credit_period_days)->toDateString();

            // Create Purchase Invoice Header
            $invoice = PurchaseInvoice::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $supplier->id,
                'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discountTotal,
                'taxable_amount' => $taxableTotal,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'total_tax' => $totalTax,
                'additional_charges' => $additionalCharges,
                'round_off' => $roundOff,
                'net_amount' => $roundedNet,
                'paid_amount' => 0,
                'due_amount' => $roundedNet,
                'payment_status' => 'unpaid',
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'created_by' => $user->id,
            ]);

            // Process line items, batches, and stock movements
            foreach ($itemsToCreate as $item) {
                $raw = $item['raw'];
                $totalReceived = $item['total_physical_quantity']; // Bill quantity + Free quantity

                // Find or create batch in destination warehouse
                $batch = ProductBatch::firstOrNew([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $raw['product_id'],
                    'batch_number' => $raw['batch_number'],
                ]);

                $isNew = !$batch->exists;
                $prevBalance = $isNew ? 0 : (float)$batch->quantity_available;

                $batch->organization_id = $user->organization_id;
                $batch->branch_id = $user->branch_id;
                $batch->manufacturing_date = $raw['manufacturing_date'] ?? null;
                $batch->expiry_date = $raw['expiry_date'];
                $batch->mrp = $item['mrp'];
                $batch->purchase_rate = $item['unit_cost'];
                $batch->sale_price = $item['sale_price'];
                $batch->wholesale_price = $item['wholesale_price'];
                $batch->supplier_id = $supplier->id;
                $batch->status = 'active';

                $batch->quantity_received += $totalReceived;
                $batch->quantity_available += $totalReceived;
                $batch->save();

                // Create Purchase Invoice Item
                $invoice->items()->create([
                    'product_id' => $raw['product_id'],
                    'product_batch_id' => $batch->id,
                    'batch_number' => $raw['batch_number'],
                    'expiry_date' => $raw['expiry_date'],
                    'manufacturing_date' => $raw['manufacturing_date'] ?? null,
                    'quantity' => $item['quantity'],
                    'free_quantity' => $item['free_quantity'],
                    'mrp' => $item['mrp'],
                    'unit_cost' => $item['unit_cost'],
                    'sale_price' => $item['sale_price'],
                    'wholesale_price' => $item['wholesale_price'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'taxable_value' => $item['taxable_value'],
                    'gst_rate' => $item['gst_rate'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'igst_amount' => $item['igst_amount'],
                    'total_amount' => $item['total_amount'],
                ]);

                // Append Stock Movement to Immutable Ledger
                StockMovement::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $raw['product_id'],
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'purchase_receipt',
                    'reference_type' => PurchaseInvoice::class,
                    'reference_id' => $invoice->id,
                    'quantity' => $totalReceived, // Positive for inward stock
                    'unit_cost' => $item['unit_cost'],
                    'balance_before' => $prevBalance,
                    'balance_after' => $prevBalance + $totalReceived,
                    'remarks' => "Purchased via Inv #{$invoice->invoice_number} from {$supplier->name}" . ($item['free_quantity'] > 0 ? " (incl. {$item['free_quantity']} free)" : ""),
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            // Update Supplier Balance & Ledger
            $newBalance = (float)$supplier->current_balance + $roundedNet;
            $supplier->current_balance = $newBalance;
            $supplier->save();

            SupplierLedgerEntry::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'supplier_id' => $supplier->id,
                'entry_date' => $data['invoice_date'],
                'entry_type' => 'purchase_invoice',
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,
                'debit_amount' => 0,
                'credit_amount' => $roundedNet, // Payable increased
                'balance_after' => $newBalance,
                'narration' => "Purchase Invoice #{$invoice->invoice_number}",
                'created_by' => $user->id,
            ]);

            // If linked to PO, update PO status
            if (!empty($data['purchase_order_id'])) {
                $po = PurchaseOrder::find($data['purchase_order_id']);
                if ($po) {
                    $po->status = 'received';
                    $po->save();
                }
            }

            // Post double-entry accounting journal
            app(AccountingService::class)->postPurchaseInvoiceJournal($invoice, $user);

            return $invoice->load(['supplier', 'items.product', 'items.batch']);
        });
    }

    /**
     * Process a Purchase Return and generate a Debit Note.
     */
    public function postPurchaseReturn(array $data, User $user): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $user) {
            $invoice = PurchaseInvoice::where('id', $data['purchase_invoice_id'])->firstOrFail();
            $supplier = Supplier::where('id', $invoice->supplier_id)->lockForUpdate()->firstOrFail();

            $subtotal = 0;
            $taxTotal = 0;
            $itemsToReturn = [];

            foreach ($data['items'] as $itemData) {
                /** @var ProductBatch $batch */
                $batch = ProductBatch::where('id', $itemData['product_batch_id'])->lockForUpdate()->firstOrFail();
                $returnQty = (float)$itemData['quantity_returned'];

                if ($returnQty <= 0) {
                    throw new InvalidArgumentException("Returned quantity must be greater than zero.");
                }

                if ((float)$batch->quantity_available < $returnQty) {
                    throw new RuntimeException("Insufficient stock in batch {$batch->batch_number} to process return. Available: {$batch->quantity_available}, Requested: {$returnQty}.");
                }

                $cost = (float)$itemData['unit_cost'];
                $taxRate = (float)($itemData['tax_rate'] ?? 12.00);
                $lineSubtotal = round($returnQty * $cost, 2);
                $lineTax = round(($lineSubtotal * $taxRate) / 100, 2);
                $lineTotal = $lineSubtotal + $lineTax;

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $itemsToReturn[] = [
                    'batch' => $batch,
                    'product_id' => $batch->product_id,
                    'quantity' => $returnQty,
                    'unit_cost' => $cost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total_amount' => $lineTotal,
                    'reason' => $itemData['reason'] ?? $data['reason'],
                ];
            }

            $totalDebitNote = $subtotal + $taxTotal;
            $debitNoteNo = 'DN-' . strtoupper(Str::random(8));

            // Create Purchase Return Header
            $purchaseReturn = PurchaseReturn::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'warehouse_id' => $invoice->warehouse_id,
                'supplier_id' => $supplier->id,
                'purchase_invoice_id' => $invoice->id,
                'debit_note_number' => $debitNoteNo,
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'reason' => $data['reason'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $totalDebitNote,
                'status' => 'approved',
                'notes' => $data['notes'] ?? null,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'created_by' => $user->id,
            ]);

            // Post stock deduction and create return line items
            foreach ($itemsToReturn as $item) {
                /** @var ProductBatch $batch */
                $batch = $item['batch'];
                $returnQty = $item['quantity'];

                $balanceBefore = (float)$batch->quantity_available;
                $balanceAfter = $balanceBefore - $returnQty;

                $batch->quantity_available = $balanceAfter;
                $batch->save();

                $purchaseReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'product_batch_id' => $batch->id,
                    'quantity_returned' => $returnQty,
                    'unit_cost' => $item['unit_cost'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'total_amount' => $item['total_amount'],
                    'reason' => $item['reason'],
                ]);

                // Append Stock Movement to Ledger
                StockMovement::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'warehouse_id' => $invoice->warehouse_id,
                    'product_id' => $item['product_id'],
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'purchase_return',
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'quantity' => -$returnQty, // Negative for outward return
                    'unit_cost' => $item['unit_cost'],
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'remarks' => "Purchase Return Debit Note #{$purchaseReturn->debit_note_number} ({$data['reason']})",
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            // Update Supplier Balance & Ledger (Debit note reduces payable)
            $newBalance = (float)$supplier->current_balance - $totalDebitNote;
            $supplier->current_balance = $newBalance;
            $supplier->save();

            SupplierLedgerEntry::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'supplier_id' => $supplier->id,
                'entry_date' => $purchaseReturn->return_date,
                'entry_type' => 'debit_note',
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $purchaseReturn->id,
                'debit_amount' => $totalDebitNote, // Payable reduced
                'credit_amount' => 0,
                'balance_after' => $newBalance,
                'narration' => "Debit Note #{$purchaseReturn->debit_note_number} against Inv #{$invoice->invoice_number}",
                'created_by' => $user->id,
            ]);

            // Post double-entry accounting journal for debit note
            app(AccountingService::class)->postPurchaseReturnJournal($purchaseReturn, $user);

            return $purchaseReturn->load(['supplier', 'items.product', 'items.batch']);
        });
    }

    /**
     * Record a payment made to a supplier.
     */
    public function recordSupplierPayment(
        int $supplierId,
        float $amount,
        string $paymentMode,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?User $user = null
    ): SupplierLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }

        return DB::transaction(function () use ($supplierId, $amount, $paymentMode, $referenceNo, $notes, $user) {
            $supplier = Supplier::where('id', $supplierId)->lockForUpdate()->firstOrFail();

            $newBalance = (float)$supplier->current_balance - $amount;
            $supplier->current_balance = $newBalance;
            $supplier->save();

            return SupplierLedgerEntry::create([
                'organization_id' => $supplier->organization_id,
                'branch_id' => $user?->branch_id ?? $supplier->organization->branches()->first()->id,
                'supplier_id' => $supplier->id,
                'entry_date' => now()->toDateString(),
                'entry_type' => 'payment',
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'balance_after' => $newBalance,
                'narration' => "Payment via {$paymentMode}" . ($referenceNo ? " (Ref: {$referenceNo})" : "") . ($notes ? " - {$notes}" : ""),
                'created_by' => $user?->id,
            ]);
        });
    }
}
