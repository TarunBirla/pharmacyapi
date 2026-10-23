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
        $response->assertSee('pharmacist@apexpharma.com');
        $response->assertSee('cashier@apexpharma.com');
        $response->assertSee('purchase@apexpharma.com');
    }

    public function test_pharmacist_can_login_via_web_session(): void
    {
        $user = User::where('email', 'pharmacist@apexpharma.com')->first();
        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email' => 'pharmacist@apexpharma.com',
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
}
