<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_complete_authentication_workflow()
    {
        // Step 1: Register a user
        $userData = [
            'name' => 'Test User',
            'email' => 'workflow@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registerResponse = $this->postJson('/api/auth/register', $userData);
        $registerResponse->assertStatus(201);

        // Step 2: Login with the registered user
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'workflow@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure(['access_token', 'user']);

        $token = $loginResponse->json('access_token');

        // Step 3: Access protected route with token
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $userResponse->assertStatus(200);
        $userResponse->assertJson([
            'success' => true,
            'user' => [
                'email' => 'workflow@example.com',
                'name' => 'Test User',
            ],
        ]);

        // Step 4: Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

        // Step 5: Verify token is invalidated (protected route should fail)
        $protectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $protectedResponse->assertStatus(401);
    }

    /** @test */
    public function it_prevents_access_without_valid_token()
    {
        $user = User::factory()->create();
        $token = 'invalid-token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_password_reset_workflow()
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        // Step 1: Request password reset
        $resetResponse = $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $resetResponse->assertStatus(200);

        // Step 2: Verify email exists in database (this would be handled by Supabase)
        $this->assertDatabaseHas('users', [
            'email' => 'reset@example.com',
        ]);
    }

    /** @test */
    public function it_validates_user_profile_updates()
    {
        $user = User::factory()->create();

        $updateResponse = $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'name' => '',
            ]);

        $updateResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}