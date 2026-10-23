<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

class PurchaseAndProcurementTest extends TestCase
{
    protected string $token;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Purchase Manager has purchase.create, purchase.post, purchase.returns, suppliers.manage
        $this->user = User::withoutGlobalScopes()->where('email', 'purchase@apexpharma.com')->first();
        $this->token = $this->user->createToken('PurchaseManagerTest')->plainTextToken;
    }

    public function test_purchase_manager_can_create_supplier(): void
    {
        $code = 'SUP-TEST-' . uniqid();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/suppliers', [
                'code' => $code,
                'name' => 'Sun Global Logistics Ltd',
                'contact_person' => 'Amitabh Saxena',
                'phone' => '+91 9844001122',
                'gstin' => '29AAECS1234K1Z0',
                'credit_period_days' => 30,
                'credit_limit' => 200000.00,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => $code,
                    'name' => 'Sun Global Logistics Ltd',
                ]
            ]);

        $this->assertDatabaseHas('suppliers', [
            'code' => $code,
        ]);
    }

    public function test_purchase_manager_can_create_purchase_order(): void
    {
        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();
        $unit = Unit::where('code', 'TAB')->firstOrFail();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/purchase-orders', [
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(4)->toDateString(),
                'notes' => 'Bulk supply for Central Warehouse',
                'items' => [
                    [
                        'product_id' => $dolo->id,
                        'unit_id' => $unit->id,
                        'quantity_ordered' => 200,
                        'unit_cost' => 22.00,
                        'discount_percent' => 5.00,
                        'tax_rate' => 12.00,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                ]
            ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => 'approved',
        ]);
    }

    public function test_posting_supplier_invoice_updates_inventory_batches_and_supplier_ledger(): void
    {
        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();

        $invNumber = 'BILL-TEST-' . uniqid();
        $batchNo = 'BATCH-' . uniqid();
        $initialSupplierBalance = (float)$supplier->current_balance;

        // Buy 50 boxes + 5 Free boxes
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/purchase-invoices', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'invoice_number' => $invNumber,
                'invoice_date' => now()->toDateString(),
                'additional_charges' => 100.00,
                'items' => [
                    [
                        'product_id' => $dolo->id,
                        'batch_number' => $batchNo,
                        'expiry_date' => now()->addMonths(20)->toDateString(),
                        'quantity' => 50,
                        'free_quantity' => 5, // 5 Free boxes
                        'mrp' => 35.00,
                        'unit_cost' => 22.00,
                        'sale_price' => 31.50,
                        'discount_percent' => 10.00,
                        'gst_rate' => 12.00,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invoice_number' => $invNumber,
                ]
            ]);

        // 1. Verify Batch Inventory received: 50 + 5 free = 55 physical units
        $batch = ProductBatch::where('batch_number', $batchNo)->firstOrFail();
        $this->assertEquals(55.0000, (float)$batch->quantity_available);
        $this->assertEquals(55.0000, (float)$batch->quantity_received);

        // 2. Verify Immutable Stock Movement Ledger
        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->id,
            'movement_type' => 'purchase_receipt',
            'quantity' => 55.0000,
        ]);

        // 3. Verify Supplier Ledger Entry
        $supplier->refresh();
        $this->assertGreaterThan($initialSupplierBalance, (float)$supplier->current_balance);

        $this->assertDatabaseHas('supplier_ledger_entries', [
            'supplier_id' => $supplier->id,
            'entry_type' => 'purchase_invoice',
        ]);
    }

    public function test_prevents_duplicate_supplier_invoice_number(): void
    {
        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();

        $dupInvoice = 'DUP-INV-' . uniqid();

        // First post: succeeds
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/purchase-invoices', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'invoice_number' => $dupInvoice,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $dolo->id,
                        'batch_number' => 'B-' . uniqid(),
                        'expiry_date' => now()->addMonths(12)->toDateString(),
                        'quantity' => 10,
                        'mrp' => 35.00,
                        'unit_cost' => 20.00,
                        'gst_rate' => 12.00,
                    ]
                ]
            ])->assertStatus(201);

        // Second post with identical supplier and invoice number: MUST BE REJECTED
        $dupResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/purchase-invoices', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'invoice_number' => $dupInvoice,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $dolo->id,
                        'batch_number' => 'B-' . uniqid(),
                        'expiry_date' => now()->addMonths(12)->toDateString(),
                        'quantity' => 10,
                        'mrp' => 35.00,
                        'unit_cost' => 20.00,
                        'gst_rate' => 12.00,
                    ]
                ]
            ]);

        $dupResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_purchase_return_deducts_stock_and_generates_debit_note(): void
    {
        $invoice = PurchaseInvoice::firstOrFail();
        $item = $invoice->items()->firstOrFail();
        $batch = $item->batch;
        $supplier = $invoice->supplier;

        $initialBatchStock = (float)$batch->quantity_available;
        $initialSupplierBalance = (float)$supplier->current_balance;

        $returnQty = 2.0000;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/purchase-returns', [
                'purchase_invoice_id' => $invoice->id,
                'reason' => 'damaged',
                'notes' => 'Damaged outer seal upon unpacking',
                'items' => [
                    [
                        'product_batch_id' => $batch->id,
                        'quantity_returned' => $returnQty,
                        'unit_cost' => (float)$item->unit_cost,
                        'tax_rate' => (float)$item->gst_rate,
                        'reason' => 'Damaged packaging',
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase return approved, stock deducted, and Debit Note posted to supplier ledger',
            ]);

        // 1. Verify batch stock was deducted
        $batch->refresh();
        $this->assertEquals($initialBatchStock - $returnQty, (float)$batch->quantity_available);

        // 2. Verify immutable ledger has purchase_return movement
        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->id,
            'movement_type' => 'purchase_return',
            'quantity' => -$returnQty,
        ]);

        // 3. Verify supplier payable was reduced
        $supplier->refresh();
        $this->assertLessThan($initialSupplierBalance, (float)$supplier->current_balance);

        // 4. Verify Debit Note in supplier ledger
        $this->assertDatabaseHas('supplier_ledger_entries', [
            'supplier_id' => $supplier->id,
            'entry_type' => 'debit_note',
        ]);
    }

    public function test_recording_supplier_payment_reduces_payable(): void
    {
        $owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->first();
        $ownerToken = $owner->createToken('AccountsPaymentTest')->plainTextToken;

        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $initialBalance = (float)$supplier->current_balance;

        $paymentAmount = 500.00;

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
                'supplier_id' => $supplier->id,
                'amount' => $paymentAmount,
                'payment_mode' => 'neft_rtgs',
                'reference_number' => 'NEFT-AXIS-998811',
                'notes' => 'Partial settlement for invoice',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $supplier->refresh();
        $this->assertEquals(round($initialBalance - $paymentAmount, 2), round((float)$supplier->current_balance, 2));

        $this->assertDatabaseHas('supplier_ledger_entries', [
            'supplier_id' => $supplier->id,
            'entry_type' => 'payment',
            'debit_amount' => $paymentAmount,
        ]);
    }
}
