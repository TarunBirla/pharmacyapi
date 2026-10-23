<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pharmacist@apexpharma.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'pharmacist_reg_number',
                        'roles',
                        'permissions',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'pharmacist@apexpharma.com',
                        'pharmacist_reg_number' => 'KA-PHARM-2018-44910',
                    ]
                ]
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pharmacist@apexpharma.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::withoutGlobalScopes()->where('email', 'pharmacist@apexpharma.com')->first();
        $token = $user->createToken('TestToken')->plainTextToken;

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'pharmacist@apexpharma.com',
                    ]
                ]
            ]);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
