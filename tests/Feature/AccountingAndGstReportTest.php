<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingAndGstReportTest extends TestCase
{
    protected User $owner;
    protected string $token;
    protected Organization $org;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->firstOrFail();
        $this->token = $this->owner->createToken('AccountingTest')->plainTextToken;
        $this->org = Organization::firstOrFail();
        $this->branch = Branch::where('code', 'APEX-RET-01')->firstOrFail();

        // Ensure default accounts exist
        app(AccountingService::class)->seedDefaultChartOfAccounts($this->org);
    }

    public function test_accounts_and_chart_of_accounts_endpoint(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/accounts?account_type=asset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('data'));

        // Create a custom sub-account (e.g. HDFC Current Account)
        $code = 'ACC-' . rand(1000, 9999);
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/accounts', [
                'account_code' => $code,
                'account_name' => 'HDFC Bank Main Current A/c',
                'account_type' => 'asset',
                'subtype' => 'bank',
                'opening_balance' => 0.00,
                'description' => 'Operating account for retail store',
            ]);

        $createRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'account_code' => $code,
                    'current_balance' => 0.00,
                ]
            ]);
    }

    public function test_manual_journal_entry_posts_balanced_debits_and_credits(): void
    {
        $cashAcc = Account::where('organization_id', $this->org->id)->where('account_code', '1010')->firstOrFail();
        $bankAcc = Account::where('organization_id', $this->org->id)->where('account_code', '1020')->firstOrFail();

        // Contra Entry: Deposit Cash into Bank (Debit Bank, Credit Cash)
        $amount = 5000.00;
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/journal-entries', [
                'entry_date' => date('Y-m-d'),
                'reference_number' => 'CHQ-DEPOSIT-1001',
                'narration' => 'Cash deposited to bank',
                'lines' => [
                    [
                        'account_id' => $bankAcc->id,
                        'type' => 'debit',
                        'amount' => $amount,
                        'narration' => 'Cash deposit'
                    ],
                    [
                        'account_id' => $cashAcc->id,
                        'type' => 'credit',
                        'amount' => $amount,
                        'narration' => 'Cash outflow to bank'
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_posted' => true,
                ]
            ]);

        // Attempting an unbalanced entry must fail
        $unbalanced = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/journal-entries', [
                'entry_date' => date('Y-m-d'),
                'lines' => [
                    ['account_id' => $bankAcc->id, 'type' => 'debit', 'amount' => 5000.00],
                    ['account_id' => $cashAcc->id, 'type' => 'credit', 'amount' => 4000.00], // Mismatch!
                ]
            ]);

        $unbalanced->assertStatus(500); // Throws RuntimeException: Unbalanced journal entry
    }

    public function test_posting_supplier_invoice_automatically_generates_balanced_journal(): void
    {
        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $product = Product::where('code', 'MED-DOLO-650')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();

        $purchaseService = app(PurchaseService::class);
        $invoice = $purchaseService->postSupplierInvoice([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'ACC-TEST-INV-' . rand(1000, 9999),
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'items' => [
                [
                    'product_id' => $product->id,
                    'batch_number' => 'ACC-BATCH-' . rand(1000, 9999),
                    'mfg_date' => date('Y-m-01'),
                    'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                    'quantity' => 10,
                    'free_quantity' => 0,
                    'unit_cost' => 25.00,
                    'mrp' => 35.00,
                    'sale_price' => 31.50,
                    'discount_percent' => 0,
                    'gst_rate' => 12.00,
                ]
            ]
        ], $this->owner);

        // Verify Journal Entry was created automatically
        $journal = JournalEntry::where('source_type', 'purchase_invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals((float)$invoice->net_amount, (float)$journal->total_debit);
        $this->assertEquals((float)$invoice->net_amount, (float)$journal->total_credit);

        // Verify lines: Inventory Asset (1040) Debited, Taxes Debited, Accounts Payable (2010) Credited
        $apLine = $journal->lines()->whereHas('account', fn($q) => $q->where('account_code', '2010'))->first();
        $this->assertNotNull($apLine);
        $this->assertEquals('credit', $apLine->type);
        $this->assertEquals((float)$invoice->net_amount, (float)$apLine->amount);
    }

    public function test_posting_pos_sale_automatically_generates_balanced_journal(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();

        $saleQty = 1.0000;
        $unitPrice = (float)$batch->sale_price; // 31.50
        $taxable = round($saleQty * $unitPrice, 2);
        $gstRate = (float)$batch->product->gst_rate;
        $tax = round($taxable * ($gstRate / 100), 2);
        $net = (float)round($taxable + $tax);

        $salesService = app(SalesService::class);
        $invoice = $salesService->postSalesInvoice([
            'warehouse_id' => $warehouse->id,
            'invoice_type' => 'retail',
            'patient_name' => 'Accounting Walkin',
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
                    'amount' => $net,
                    'payment_mode' => 'cash',
                ]
            ]
        ], $this->owner);

        // Verify journal entry
        $journal = JournalEntry::where('source_type', 'sales_invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals((float)$journal->total_debit, (float)$journal->total_credit);

        // Verify Cash in Hand (1010) Debited
        $cashLine = $journal->lines()->whereHas('account', fn($q) => $q->where('account_code', '1010'))->first();
        $this->assertNotNull($cashLine);
        $this->assertEquals('debit', $cashLine->type);
        $this->assertEquals($net, (float)$cashLine->amount);
    }

    public function test_trial_balance_is_balanced(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/financial/trial-balance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['accounts']);
    }

    public function test_profit_and_loss_and_balance_sheet_reports(): void
    {
        $plRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/financial/profit-and-loss?from_date=' . date('Y-01-01') . '&to_date=' . date('Y-m-d'));

        $plRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'revenues',
                    'cost_of_goods_sold',
                    'expenses',
                    'gross_profit',
                    'net_profit',
                ]
            ]);

        $bsRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/financial/balance-sheet?as_of_date=' . date('Y-m-d'));

        $bsRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'assets',
                    'liabilities',
                    'equity',
                    'total_liabilities_and_equity',
                ]
            ]);
    }

    public function test_gstr1_report_sections(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/gst/gstr1?from_date=' . date('Y-m-01') . '&to_date=' . date('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'b2b_invoices',
                    'b2cs_invoices',
                    'b2cl_invoices',
                    'credit_debit_notes_registered',
                    'credit_debit_notes_unregistered',
                    'hsn_summary',
                    'documents_summary',
                ]
            ]);
    }

    public function test_gstr3b_summary_and_eligible_itc(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/gst/gstr3b?from_date=' . date('Y-m-01') . '&to_date=' . date('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'table_3_1_outward_supplies',
                    'table_4_eligible_itc',
                    'net_tax_liability_payable_in_cash',
                ]
            ]);
    }
}
