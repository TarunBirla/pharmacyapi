<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BladeFrontendWebTest extends TestCase
{
    public function test_login_page_renders_clean_white_theme_with_demo_accounts(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('APEX PHARMA ERP');
        $response->assertSee('admin@pharmacy.com');
        $response->assertSee('owner@apollocare.com');
        $response->assertSee('pharmacist@apollocare.com');
        $response->assertSee('cashier@apollocare.com');
        $response->assertSee('purchaser@apollocare.com');
    }

    public function test_all_five_user_roles_can_login_successfully(): void
    {
        $rolesData = [
            ['email' => 'admin@pharmacy.com', 'redirect' => '/pos'],
            ['email' => 'owner@apollocare.com', 'redirect' => '/pos'],
            ['email' => 'pharmacist@apollocare.com', 'redirect' => '/pos'],
            ['email' => 'cashier@apollocare.com', 'redirect' => '/pos'],
            ['email' => 'purchaser@apollocare.com', 'redirect' => '/procurement'],
        ];

        foreach ($rolesData as $role) {
            $user = User::where('email', $role['email'])->first();
            $this->assertNotNull($user, "User {$role['email']} must exist in database");

            $response = $this->post('/login', [
                'email' => $role['email'],
                'password' => 'Password123!',
            ]);

            $response->assertRedirect($role['redirect']);
            $this->assertAuthenticatedAs($user);

            // Test logout
            $logoutResponse = $this->actingAs($user)->post('/logout');
            $logoutResponse->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_pharmacist_can_login_via_web_session(): void
    {
        $user = User::where('email', 'pharmacist@apollocare.com')->first()
            ?? User::where('email', 'pharmacist@apexpharma.com')->first();
        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/pos');
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_pos_counter(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/pos');

        $response->assertStatus(200);
        $response->assertSee('Retail POS Counter Billing');
        $response->assertSee('Current Bill Cart');
    }

    public function test_authenticated_user_can_access_inventory(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/inventory');

        $response->assertStatus(200);
        $response->assertSee('Medicine Master');
        $response->assertSee('Full Catalog');
        $response->assertSee('Add New Medicine');
    }

    public function test_authenticated_user_can_access_procurement(): void
    {
        $user = User::where('email', 'purchase@apexpharma.com')->first() 
            ?? User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/procurement');

        $response->assertStatus(200);
        $response->assertSee('Supplier Directory');
    }

    public function test_authenticated_user_can_access_sales_register(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/sales');

        $response->assertStatus(200);
        $response->assertSee('Sales Register');
    }

    public function test_authenticated_user_can_access_gst_reports(): void
    {
        $user = User::where('email', 'owner@apexpharma.com')->first() 
            ?? User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/reports/gst');

        $response->assertStatus(200);
        $response->assertSee('GST Compliance');
        $response->assertSee('GSTR-3B Monthly Return Summary');
    }

    public function test_authenticated_user_can_access_financial_reports(): void
    {
        $user = User::where('email', 'owner@apexpharma.com')->first() 
            ?? User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/reports/financials');

        $response->assertStatus(200);
        $response->assertSee('Financial Accounting');
        $response->assertSee('Income Statement');
        $response->assertSee('Standard Trial Balance Ledger');
    }

    public function test_authenticated_user_can_access_ai_analytics(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/ai');

        $response->assertStatus(200);
        $response->assertSee('AI Predictive Intelligence');
        $response->assertSee('Demand Forecast');
    }

    public function test_authenticated_user_can_access_audit_logs(): void
    {
        $user = User::where('email', 'superadmin@pharmaerp.local')->first() 
            ?? User::where('email', 'owner@apexpharma.com')->first();

        $response = $this->actingAs($user)->get('/audit');

        $response->assertStatus(200);
        $response->assertSee('System Security Audit Trail');
    }

    public function test_pos_web_checkout_and_receipt_generation(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();
        $unit = \App\Models\Unit::first();
        $warehouse = \App\Models\Warehouse::where('branch_id', $user->branch_id)->first() ?? \App\Models\Warehouse::first();

        $product = \App\Models\Product::create([
            'organization_id' => $user->organization_id,
            'code' => 'TEST-POS-' . rand(1000, 9999),
            'name' => 'Web POS Test Drug',
            'dosage_form' => 'tablet',
            'hsn_code' => '3004',
            'gst_rate' => 12,
            'primary_unit_id' => $unit->id,
            'prescription_type' => 'otc',
            'is_active' => true,
        ]);

        $batch = \App\Models\ProductBatch::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'batch_number' => 'TEST-BTH-' . rand(1000, 9999),
            'manufacturing_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'mrp' => 50.00,
            'purchase_rate' => 30.00,
            'sale_price' => 45.00,
            'quantity_received' => 20,
            'quantity_available' => 20,
            'status' => 'active',
        ]);

        $checkoutResponse = $this->actingAs($user)->postJson('/pos/checkout', [
            'doctor_name' => 'Dr. A. K. Sharma',
            'prescription_number' => 'RX-9988',
            'items' => [
                [
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'gst_rate' => 12,
                ]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 100.00,
                ]
            ]
        ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertJson(['success' => true]);

        $invoiceId = $checkoutResponse->json('invoice_id');
        $this->assertNotNull($invoiceId);

        // Verify printable receipt view
        $receiptResponse = $this->actingAs($user)->get("/pos/receipt/{$invoiceId}");
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('Tax Invoice:');
        $receiptResponse->assertSee($product->name);
        $receiptResponse->assertSee('NET PAYABLE:');
    }

    public function test_inventory_add_medicine_and_batch_creation(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();
        $testBarcode = '890' . rand(1000000000, 9999999999);

        $response = $this->actingAs($user)->post('/inventory/products', [
            'name' => 'Dolo 650 Fast Release',
            'generic_name' => 'Paracetamol',
            'dosage_form' => 'tablet',
            'strength' => '650mg',
            'barcode' => $testBarcode,
            'hsn_code' => '3004',
            'gst_rate' => 12,
            'prescription_type' => 'otc',
            'purchase_rate' => 22.50,
            'retail_price' => 31.00,
            'mrp' => 35.00,
            'reorder_level' => 30,
        ]);

        $response->assertRedirect('/inventory');

        $this->assertDatabaseHas('products', [
            'name' => 'Dolo 650 Fast Release',
            'organization_id' => $user->organization_id,
        ]);
    }
}
