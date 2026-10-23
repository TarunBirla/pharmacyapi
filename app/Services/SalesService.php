<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Payment;
use App\Models\PosHeldBill;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SalesService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Atomically post a retail POS or wholesale invoice.
     */
    public function postSalesInvoice(array $data, User $user): SalesInvoice
    {
        return DB::transaction(function () use ($data, $user) {
            /** @var Branch $branch */
            $branch = Branch::where('id', $user->branch_id)->lockForUpdate()->firstOrFail();

            // Generate branch-scoped invoice sequence safely
            $invoiceNumber = sprintf('%s-%06d', $branch->invoice_prefix ?: 'INV', $branch->next_invoice_number);
            $branch->increment('next_invoice_number');

            $warehouseId = $data['warehouse_id'] ?? $branch->warehouses()->where('is_default', true)->first()->id;

            // Customer verification
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('id', $data['customer_id'])->lockForUpdate()->firstOrFail();
            }

            // Prescription check: If any line has prescription_type not 'otc', check prescription details
            $requiresPrescription = false;
            foreach ($data['items'] as $item) {
                $prod = Product::find($item['product_id']);
                if ($prod && in_array($prod->prescription_type, ['schedule_h', 'schedule_h1', 'schedule_x', 'narcotic'], true)) {
                    $requiresPrescription = true;
                    break;
                }
            }

            if ($requiresPrescription && empty($data['prescription_id']) && empty($data['doctor_name']) && empty($data['doctor_id'])) {
                throw new RuntimeException("Prescription or prescribing doctor information is required for Schedule H/H1/X drugs in this order.");
            }

            // Calculation of line items and tax
            $subtotal = 0;
            $itemDiscountTotal = 0;
            $taxableTotal = 0;
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
            $linesToProcess = [];

            foreach ($data['items'] as $itemData) {
                $qty = (float)$itemData['quantity'];
                $price = (float)$itemData['unit_price'];
                $discPercent = (float)($itemData['discount_percent'] ?? 0);
                $freeQty = (float)($itemData['free_quantity'] ?? 0);

                /** @var ProductBatch $batch */
                $batch = ProductBatch::where('id', $itemData['product_batch_id'])->lockForUpdate()->firstOrFail();

                // Validation
                if ($batch->isExpired()) {
                    throw new RuntimeException("Cannot sell expired batch {$batch->batch_number}.");
                }

                if ((float)$batch->quantity_available < ($qty + $freeQty)) {
                    throw new RuntimeException("Insufficient stock in batch {$batch->batch_number}. Available: {$batch->quantity_available}, Requested: " . ($qty + $freeQty));
                }

                $gross = $qty * $price;
                $discAmt = round(($gross * $discPercent) / 100, 2);
                $taxable = $gross - $discAmt;

                $gstRate = (float)($itemData['gst_rate'] ?? $batch->product->gst_rate);
                $taxAmt = round(($taxable * $gstRate) / 100, 2);
                $cgst = round($taxAmt / 2, 2);
                $sgst = round($taxAmt / 2, 2);
                $igst = 0;

                $lineTotal = $taxable + $taxAmt;

                $subtotal += $gross;
                $itemDiscountTotal += $discAmt;
                $taxableTotal += $taxable;
                $cgstTotal += $cgst;
                $sgstTotal += $sgst;
                $igstTotal += $igst;

                $linesToProcess[] = [
                    'batch' => $batch,
                    'product_id' => $batch->product_id,
                    'quantity' => $qty,
                    'free_quantity' => $freeQty,
                    'mrp' => (float)$batch->mrp,
                    'unit_price' => $price,
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

            // Bill-level discounts
            $billDiscountPercent = (float)($data['bill_discount_percent'] ?? 0);
            $billDiscountAmount = (float)($data['bill_discount_amount'] ?? 0);
            if ($billDiscountPercent > 0 && $billDiscountAmount == 0) {
                $billDiscountAmount = round(($taxableTotal * $billDiscountPercent) / 100, 2);
            }

            $totalTax = $cgstTotal + $sgstTotal + $igstTotal;
            $netBeforeRound = ($taxableTotal - $billDiscountAmount) + $totalTax;
            $netAmount = round($netBeforeRound);
            $roundOff = round($netAmount - $netBeforeRound, 2);

            // Active cashier shift lookup
            $openShift = CashierShift::where('branch_id', $branch->id)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            // Create Sales Invoice Header
            $invoice = SalesInvoice::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouseId,
                'customer_id' => $customer?->id,
                'doctor_id' => $data['doctor_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'cashier_shift_id' => $openShift?->id,
                'invoice_number' => $invoiceNumber,
                'invoice_type' => $data['invoice_type'] ?? 'retail',
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'item_discount_amount' => $itemDiscountTotal,
                'bill_discount_percent' => $billDiscountPercent,
                'bill_discount_amount' => $billDiscountAmount,
                'taxable_amount' => $taxableTotal,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'total_tax' => $totalTax,
                'round_off' => $roundOff,
                'net_amount' => $netAmount,
                'paid_amount' => 0,
                'due_amount' => $netAmount,
                'payment_status' => 'unpaid',
                'status' => 'posted',
                'patient_name' => $data['patient_name'] ?? null,
                'doctor_name' => $data['doctor_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);

            // Deduct batch stock and log immutable movements
            foreach ($linesToProcess as $line) {
                /** @var ProductBatch $batch */
                $batch = $line['batch'];
                $totalDeduction = $line['quantity'] + $line['free_quantity'];

                $balanceBefore = (float)$batch->quantity_available;
                $balanceAfter = $balanceBefore - $totalDeduction;

                $batch->quantity_available = $balanceAfter;
                $batch->save();

                $invoice->items()->create([
                    'product_id' => $line['product_id'],
                    'product_batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'quantity' => $line['quantity'],
                    'free_quantity' => $line['free_quantity'],
                    'mrp' => $line['mrp'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'],
                    'discount_amount' => $line['discount_amount'],
                    'taxable_value' => $line['taxable_value'],
                    'gst_rate' => $line['gst_rate'],
                    'cgst_amount' => $line['cgst_amount'],
                    'sgst_amount' => $line['sgst_amount'],
                    'igst_amount' => $line['igst_amount'],
                    'total_amount' => $line['total_amount'],
                ]);

                StockMovement::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $branch->id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $line['product_id'],
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'sale_deduction',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'quantity' => -$totalDeduction,
                    'unit_cost' => $batch->purchase_rate,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'remarks' => "POS Invoice #{$invoice->invoice_number}",
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            // Payment processing (supports Split Payments: e.g. Cash + Card + UPI)
            $paidTotal = 0;
            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $paymentData) {
                    $amt = (float)$paymentData['amount'];
                    if ($amt <= 0) continue;

                    $paidTotal += $amt;

                    Payment::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $branch->id,
                        'customer_id' => $customer?->id,
                        'sales_invoice_id' => $invoice->id,
                        'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
                        'payment_date' => $invoice->invoice_date,
                        'amount' => $amt,
                        'payment_mode' => $paymentData['payment_mode'], // cash, card, upi, bank_transfer
                        'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                        'created_by' => $user->id,
                    ]);
                }
            }

            $dueAmount = max(0, $netAmount - $paidTotal);
            $invoice->paid_amount = $paidTotal;
            $invoice->due_amount = $dueAmount;

            if ($dueAmount == 0) {
                $invoice->payment_status = 'paid';
            } elseif ($paidTotal > 0) {
                $invoice->payment_status = 'partially_paid';
            } else {
                $invoice->payment_status = ($customer && $data['invoice_type'] === 'wholesale') ? 'credit' : 'unpaid';
            }
            $invoice->save();

            // Customer ledger tracking for credit sales or outstanding balance
            if ($customer) {
                if ($dueAmount > 0) {
                    if (!$customer->isCreditAllowed($dueAmount)) {
                        throw new RuntimeException("Customer '{$customer->name}' exceeds allowed credit limit (Limit: ₹{$customer->credit_limit}, Current: ₹{$customer->current_balance}).");
                    }
                }

                $newBalance = (float)$customer->current_balance + $dueAmount;
                $customer->current_balance = $newBalance;
                $customer->save();

                // Record invoice debit in customer ledger
                CustomerLedgerEntry::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'entry_date' => $invoice->invoice_date,
                    'entry_type' => 'sales_invoice',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'debit_amount' => $netAmount, // Total bill amount receivable
                    'credit_amount' => $paidTotal, // Amount paid upfront
                    'balance_after' => $newBalance,
                    'narration' => "Invoice #{$invoice->invoice_number}" . ($dueAmount > 0 ? " (Credit ₹{$dueAmount})" : " (Settled)"),
                    'created_by' => $user->id,
                ]);
            }

            // Post double-entry accounting journal
            $invoice->load('payments');
            app(AccountingService::class)->postSalesInvoiceJournal($invoice, $user);

            return $invoice->load(['customer', 'items.product', 'items.batch', 'payments']);
        });
    }

    /**
     * Post a Sales Return and generate a Credit Note.
     */
    public function postSalesReturn(array $data, User $user): SalesReturn
    {
        return DB::transaction(function () use ($data, $user) {
            $invoice = SalesInvoice::where('id', $data['sales_invoice_id'])->firstOrFail();
            $customer = $invoice->customer;

            $subtotal = 0;
            $taxTotal = 0;
            $itemsToReturn = [];

            foreach ($data['items'] as $itemData) {
                $invItem = SalesInvoiceItem::where('id', $itemData['sales_invoice_item_id'])->firstOrFail();
                $returnQty = (float)$itemData['quantity_returned'];

                if ($returnQty <= 0) {
                    throw new InvalidArgumentException("Return quantity must be greater than zero.");
                }

                // Check previously returned quantities against original sale
                $alreadyReturned = SalesReturnItem::where('sales_invoice_item_id', $invItem->id)->sum('quantity_returned');
                if (($alreadyReturned + $returnQty) > (float)$invItem->quantity) {
                    throw new RuntimeException("Cannot return {$returnQty}. Sold: {$invItem->quantity}, Already returned: {$alreadyReturned}.");
                }

                $price = (float)$invItem->unit_price;
                $lineSubtotal = round($returnQty * $price, 2);
                $lineTax = round(($lineSubtotal * (float)$invItem->gst_rate) / 100, 2);
                $lineTotal = $lineSubtotal + $lineTax;

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $itemsToReturn[] = [
                    'inv_item' => $invItem,
                    'quantity' => $returnQty,
                    'unit_price' => $price,
                    'tax_amount' => $lineTax,
                    'total_amount' => $lineTotal,
                    'condition' => $itemData['condition'] ?? 'restockable', // restockable, damaged, expired
                ];
            }

            $creditNoteNo = 'CN-' . strtoupper(Str::random(8));
            $totalCreditAmount = $subtotal + $taxTotal;

            $salesReturn = SalesReturn::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $invoice->branch_id,
                'warehouse_id' => $invoice->warehouse_id,
                'customer_id' => $customer?->id,
                'sales_invoice_id' => $invoice->id,
                'credit_note_number' => $creditNoteNo,
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'reason' => $data['reason'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $totalCreditAmount,
                'refund_mode' => $data['refund_mode'] ?? 'cash_refund',
                'refund_status' => 'refunded',
                'status' => 'approved',
                'created_by' => $user->id,
                'approved_by' => $user->id,
            ]);

            foreach ($itemsToReturn as $item) {
                $invItem = $item['inv_item'];
                $batch = $invItem->batch;
                $condition = $item['condition'];
                $qty = $item['quantity'];

                $salesReturn->items()->create([
                    'sales_invoice_item_id' => $invItem->id,
                    'product_id' => $invItem->product_id,
                    'product_batch_id' => $batch->id,
                    'quantity_returned' => $qty,
                    'unit_price' => $item['unit_price'],
                    'tax_amount' => $item['tax_amount'],
                    'total_amount' => $item['total_amount'],
                    'condition' => $condition,
                ]);

                // Only restockable condition returns to available inventory!
                if ($condition === 'restockable') {
                    $prevStock = (float)$batch->quantity_available;
                    $batch->quantity_available += $qty;
                    $batch->save();

                    StockMovement::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $invoice->branch_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'movement_type' => 'sale_return',
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $salesReturn->id,
                        'quantity' => $qty, // Positive for restocked return
                        'unit_cost' => $batch->purchase_rate,
                        'balance_before' => $prevStock,
                        'balance_after' => $prevStock + $qty,
                        'remarks' => "Restocked return Credit Note #{$salesReturn->credit_note_number}",
                        'created_by' => $user->id,
                        'created_at' => now(),
                    ]);
                } else {
                    // Damaged / Expired returned medicine moves to damaged quantity
                    $batch->quantity_damaged += $qty;
                    $batch->save();

                    StockMovement::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $invoice->branch_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'movement_type' => 'damage_write_off',
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $salesReturn->id,
                        'quantity' => 0,
                        'unit_cost' => $batch->purchase_rate,
                        'balance_before' => (float)$batch->quantity_available,
                        'balance_after' => (float)$batch->quantity_available,
                        'remarks' => "Returned as {$condition} - non-saleable stock write-off",
                        'created_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }
            }

            // Update customer balance & ledger if customer exists
            if ($customer) {
                $newBalance = max(0, (float)$customer->current_balance - $totalCreditAmount);
                $customer->current_balance = $newBalance;
                $customer->save();

                CustomerLedgerEntry::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $invoice->branch_id,
                    'customer_id' => $customer->id,
                    'entry_date' => $salesReturn->return_date,
                    'entry_type' => 'credit_note',
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCreditAmount, // Reduces customer debt
                    'balance_after' => $newBalance,
                    'narration' => "Credit Note #{$salesReturn->credit_note_number} against Inv #{$invoice->invoice_number}",
                    'created_by' => $user->id,
                ]);
            }

            // Post double-entry accounting journal
            app(AccountingService::class)->postSalesReturnJournal($salesReturn, $user);

            return $salesReturn->load(['customer', 'items.product', 'items.batch']);
        });
    }

    /**
     * Start Cashier Shift for POS register.
     */
    public function startShift(float $openingCash, User $user): CashierShift
    {
        $existing = CashierShift::where('branch_id', $user->branch_id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $existing;
        }

        return CashierShift::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'shift_code' => 'SHIFT-' . strtoupper(Str::random(6)),
            'started_at' => now(),
            'opening_cash' => $openingCash,
            'status' => 'open',
        ]);
    }

    /**
     * Close Cashier Shift with cash reconciliation.
     */
    public function closeShift(int $shiftId, float $closingCash, ?string $notes = null): CashierShift
    {
        /** @var CashierShift $shift */
        $shift = CashierShift::findOrFail($shiftId);

        // Sum cash payments during this shift
        $cashSales = Payment::where('branch_id', $shift->branch_id)
            ->where('payment_mode', 'cash')
            ->whereBetween('created_at', [$shift->started_at, now()])
            ->sum('amount');

        $expected = (float)$shift->opening_cash + (float)$cashSales;
        $discrepancy = $closingCash - $expected;

        $shift->closing_cash = $closingCash;
        $shift->expected_cash = $expected;
        $shift->discrepancy = $discrepancy;
        $shift->ended_at = now();
        $shift->status = 'closed';
        $shift->notes = $notes;
        $shift->save();

        return $shift;
    }
}
