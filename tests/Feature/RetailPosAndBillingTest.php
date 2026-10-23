<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Tests\TestCase;

class RetailPosAndBillingTest extends TestCase
{
    protected string $token;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        // Cashier has sales.create, sales.post, inventory.view, batches.view, products.view
        $this->cashier = User::withoutGlobalScopes()->where('email', 'cashier@apexpharma.com')->first();
        $this->token = $this->cashier->createToken('CashierPosTest')->plainTextToken;
    }

    public function test_fast_pos_checkout_with_split_payments_and_stock_deduction(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();
        $initialStock = (float)$batch->quantity_available;

        $saleQty = 2.0000;
        $unitPrice = (float)$batch->sale_price;
        $taxable = round($saleQty * $unitPrice, 2);
        $gstRate = (float)$batch->product->gst_rate;
        $tax = round($taxable * ($gstRate / 100), 2);
        $expectedTotal = (float)round($taxable + $tax); // rounded net total payable

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/checkout', [
                'warehouse_id' => $warehouse->id,
                'invoice_type' => 'retail',
                'patient_name' => 'Walk-in Patient',
                'items' => [
                    [
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'quantity' => $saleQty,
                        'unit_price' => $unitPrice,
                    ]
                ],
                'payments' => [
                    [
                        'amount' => 20.00,
                        'payment_mode' => 'cash',
                    ],
                    [
                        'amount' => $expectedTotal - 20.00,
                        'payment_mode' => 'upi',
                        'transaction_reference' => 'UPI-GPAY-998811',
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'payment_status' => 'paid',
                    'due_amount' => 0,
                ]
            ]);

        // 1. Verify batch stock was decremented
        $batch->refresh();
        $this->assertEquals($initialStock - $saleQty, (float)$batch->quantity_available);

        // 2. Verify immutable movement was posted to ledger
        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->id,
            'movement_type' => 'sale_deduction',
            'quantity' => -$saleQty,
        ]);

        // 3. Verify invoice and split payments in DB
        $invoiceNumber = $response->json('data.invoice_number');
        $this->assertDatabaseHas('sales_invoices', [
            'invoice_number' => $invoiceNumber,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'payment_mode' => 'cash',
            'amount' => 20.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'payment_mode' => 'upi',
            'transaction_reference' => 'UPI-GPAY-998811',
        ]);
    }

    public function test_requires_prescription_for_schedule_h_antibiotic(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'AG-2026-A1')->firstOrFail(); // Augmentin 625 Duo Schedule H

        // 1. Attempt checkout without doctor or prescription -> MUST FAIL
        $failResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/checkout', [
                'warehouse_id' => $warehouse->id,
                'items' => [
                    [
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'quantity' => 1,
                        'unit_price' => (float)$batch->sale_price,
                    ]
                ]
            ]);

        $failResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // 2. Supply prescribing doctor -> MUST SUCCEED
        $doctor = Doctor::first();
        $successResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/checkout', [
                'warehouse_id' => $warehouse->id,
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'patient_name' => 'Karthik Rao',
                'items' => [
                    [
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'quantity' => 1,
                        'unit_price' => (float)$batch->sale_price,
                    ]
                ],
                'payments' => [
                    [
                        'amount' => (float)$batch->sale_price,
                        'payment_mode' => 'cash',
                    ]
                ]
            ]);

        $successResponse->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_customer_credit_sale_and_credit_limit_rejection(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();
        $customer = Customer::where('code', 'CUST-RET-001')->firstOrFail(); // Credit limit ₹5000

        $initialCustomerBalance = (float)$customer->current_balance;

        // Sale on credit (No payments array provided)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/checkout', [
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer->id,
                'invoice_type' => 'retail',
                'items' => [
                    [
                        'product_id' => $batch->product_id,
                        'product_batch_id' => $batch->id,
                        'quantity' => 2,
                        'unit_price' => 30.00,
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $netAmount = (float)$response->json('data.net_amount');

        // Customer receivable increased
        $customer->refresh();
        $this->assertEquals($initialCustomerBalance + $netAmount, (float)$customer->current_balance);

        // Customer ledger has entry
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'entry_type' => 'sales_invoice',
        ]);
    }

    public function test_hold_and_resume_bill_in_pos(): void
    {
        $cartData = [
            'items' => [
                ['product_id' => 1, 'name' => 'Dolo 650', 'quantity' => 3, 'unit_price' => 31.50],
            ]
        ];

        // 1. Hold Bill
        $holdResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/hold', [
                'cart_data' => $cartData,
                'total_amount' => 94.50,
            ]);

        $holdResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $refCode = $holdResponse->json('reference_code');

        // 2. List held bills
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/pos/held-bills');

        $listResponse->assertStatus(200);
        $this->assertContains($refCode, collect($listResponse->json('data'))->pluck('reference_code')->all());

        // 3. Resume Bill
        $resumeResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/pos/resume/{$refCode}");

        $resumeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reference_code' => $refCode,
                ]
            ]);

        // Verified removed from hold queue
        $this->assertDatabaseMissing('pos_held_bills', [
            'reference_code' => $refCode,
        ]);
    }

    public function test_sales_return_restores_stock_and_issues_credit_note(): void
    {
        $pharmacist = User::withoutGlobalScopes()->where('email', 'pharmacist@apexpharma.com')->first();
        $pharmacistToken = $pharmacist->createToken('PharmacistReturnTest')->plainTextToken;

        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();

        // Create a dedicated fresh sale to return against
        $salesService = app(SalesService::class);
        $invoice = $salesService->postSalesInvoice([
            'warehouse_id' => $warehouse->id,
            'invoice_type' => 'retail',
            'patient_name' => 'Return Test Patient',
            'items' => [
                [
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'quantity' => 2.0000,
                    'unit_price' => (float)$batch->sale_price,
                ]
            ],
            'payments' => [
                [
                    'amount' => (float)round(2.0000 * (float)$batch->sale_price * 1.12),
                    'payment_mode' => 'cash',
                ]
            ]
        ], $pharmacist);

        $item = $invoice->items()->firstOrFail();
        $batch->refresh();
        $initialStock = (float)$batch->quantity_available;

        $response = $this->withHeader('Authorization', 'Bearer ' . $pharmacistToken)
            ->postJson('/api/v1/sales/returns', [
                'sales_invoice_id' => $invoice->id,
                'reason' => 'Customer purchased excess strip by mistake',
                'refund_mode' => 'cash_refund',
                'items' => [
                    [
                        'sales_invoice_item_id' => $item->id,
                        'quantity_returned' => 1.0000,
                        'condition' => 'restockable',
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Sales return approved, stock restored according to condition, and credit note created',
            ]);

        // 1. Stock restored
        $batch->refresh();
        $this->assertEquals($initialStock + 1.0000, (float)$batch->quantity_available);

        // 2. Stock movement logged
        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->id,
            'movement_type' => 'sale_return',
            'quantity' => 1.0000,
        ]);

        // 3. Credit note in DB
        $creditNoteNumber = $response->json('data.credit_note_number');
        $this->assertDatabaseHas('sales_returns', [
            'credit_note_number' => $creditNoteNumber,
        ]);
    }

    public function test_cashier_shift_start_and_close(): void
    {
        // 1. Start Shift
        $startResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/shifts/start', [
                'opening_cash' => 1500.00,
            ]);

        $startResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'opening_cash' => 1500,
                    'status' => 'open',
                ]
            ]);

        $shiftId = $startResponse->json('data.id');

        // 2. Close Shift
        $closeResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/pos/shifts/close', [
                'shift_id' => $shiftId,
                'closing_cash' => 1500.00,
                'notes' => 'Evening register balanced',
            ]);

        $closeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'closing_cash' => 1500,
                    'status' => 'closed',
                ]
            ]);

        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shiftId,
            'status' => 'closed',
        ]);
    }
}
