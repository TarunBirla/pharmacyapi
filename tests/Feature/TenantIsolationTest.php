<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_isolation_prevents_viewing_other_organization_data(): void
    {
        // Create second organization
        $otherOrg = Organization::firstOrCreate(
            ['code' => 'METRO-PHARMA'],
            [
                'name' => 'Metro Care Pharmacy',
                'email' => 'admin@metropharma.local',
                'phone' => '+91 9988776655',
                'status' => 'active',
            ]
        );

        $otherBranch = Branch::firstOrCreate(
            ['organization_id' => $otherOrg->id, 'code' => 'METRO-MAIN'],
            [
                'name' => 'Metro Main Counter',
                'business_type' => 'retail',
                'status' => 'active',
            ]
        );

        // Login as Apex Pharmacist
        $apexPharmacist = User::withoutGlobalScopes()->where('email', 'pharmacist@apexpharma.com')->first();
        $token = $apexPharmacist->createToken('Test')->plainTextToken;

        // Query branches - should ONLY return Apex branches, NEVER Metro branch
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/branches');

        $response->assertStatus(200);
        $branchCodes = collect($response->json('data.data'))->pluck('code')->all();

        $this->assertContains('APEX-RET-01', $branchCodes);
        $this->assertNotContains('METRO-MAIN', $branchCodes);

        // Attempt direct access or cross-tenant injection
        $crossTenantResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/branches', [
                'organization_id' => $otherOrg->id,
                'name' => 'Hacked Branch',
                'code' => 'HACK-01',
                'business_type' => 'retail',
            ]);

        // Blocked by EnsureTenantAccess middleware
        $crossTenantResponse->assertStatus(403);
    }

    public function test_cashier_cannot_view_audit_logs(): void
    {
        $cashier = User::withoutGlobalScopes()->where('email', 'cashier@apexpharma.com')->first();
        $token = $cashier->createToken('CashierToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/audit-logs');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_super_admin_can_view_audit_logs(): void
    {
        $superAdmin = User::withoutGlobalScopes()->where('email', 'superadmin@pharmaerp.local')->first();
        $adminToken = $superAdmin->createToken('AdminToken')->plainTextToken;

        $adminResponse = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/v1/audit-logs');

        $adminResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
