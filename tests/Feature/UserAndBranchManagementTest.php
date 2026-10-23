<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Tests\TestCase;

class UserAndBranchManagementTest extends TestCase
{
    public function test_org_owner_can_create_a_new_pharmacist(): void
    {
        $owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->first();
        $token = $owner->createToken('OwnerToken')->plainTextToken;
        $uniqueEmail = 'pharmacist.' . uniqid() . '@apexpharma.com';

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/users', [
                'name' => 'Kavitha R. (Pharmacist)',
                'email' => $uniqueEmail,
                'phone' => '+91 9911223344',
                'pharmacist_reg_number' => 'KA-PHARM-2022-77441',
                'password' => 'SecurePass123!',
                'role_slugs' => ['pharmacist'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $uniqueEmail,
                    'pharmacist_reg_number' => 'KA-PHARM-2022-77441',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'organization_id' => $owner->organization_id,
        ]);
    }

    public function test_user_can_switch_assigned_branch(): void
    {
        $owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->first();
        $wholesaleBranch = Branch::where('code', 'APEX-WHL-01')->first();
        $token = $owner->createToken('OwnerToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/switch-branch', [
                'branch_id' => $wholesaleBranch->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'branch_id' => $wholesaleBranch->id,
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'branch_id' => $wholesaleBranch->id,
        ]);
    }
}
