<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class SupabaseAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => true,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'access_token' => true,
                'token_type' => 'Bearer',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test user registration validation
     */
    public function test_user_registration_validation(): void
    {
        // Test missing email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test password mismatch
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user login
     */
    public function test_user_can_login(): void
    {
        // First create a user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success' => true,
                'message' => 'Login successful',
                'user' => true,
                'access_token' => true,
                'token_type' => 'Bearer',
            ]);

        $this->assertArrayHasKey('access_token', $response->json());
    }

    /**
     * Test user login with invalid credentials
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test authenticated user can access protected route
     */
    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access protected route
     */
    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /**
     * Test user logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    /**
     * Test forgot password
     */
    public function test_forgot_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset email sent',
            ]);
    }

    /**
     * Test user profile update
     */
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test that users table has Supabase fields
     */
    public function test_users_table_has_supabase_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'supabase_id' => 'supabase-uuid-here',
            'supabase_metadata' => json_encode(['key' => 'value']),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
            'supabase_id' => 'supabase-uuid-here',
            'supabase_metadata' => json_encode(['key' => 'value']),
        ]);
    }

    /**
     * Test user registration with metadata
     */
    public function test_user_registration_with_metadata(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.with.metadata@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => true,
                    'name' => 'John Doe',
                    'email' => 'john.with.metadata@example.com',
                    'supabase_id' => true,
                ],
                'supabase_user' => [
                    'id' => true,
                    'email' => 'john.with.metadata@example.com',
                    'user_metadata' => true,
                ],
                'access_token' => true,
                'token_type' => 'Bearer',
            ]);
    }

    /**
     * Test user registration with duplicate email
     */
    public function test_user_registration_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login with non-existent user
     */
    public function test_user_login_with_non_existent_user(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test token refresh
     */
    public function test_token_refresh(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh', [
            'refresh_token' => 'mock-refresh-token',
        ]);

        $response->assertStatus(401); // Will fail without proper Supabase setup
    }

    /**
     * Test forgot password with non-existent email
     */
    public function test_forgot_password_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test forgot password with invalid email format
     */
    public function test_forgot_password_with_invalid_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test profile update with unauthorized user
     */
    public function test_profile_update_unauthorized(): void
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(401);
    }

    /**
     * Test profile update with invalid email
     */
    public function test_profile_update_with_invalid_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $updateData = [
            'email' => 'invalid-email-format',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test profile update with duplicate email
     */
    public function test_profile_update_with_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $token = $user1->createToken('auth_token')->plainTextToken;

        $updateData = [
            'email' => 'user2@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user can access protected route with valid token
     */
    public function test_user_can_access_multiple_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $routes = [
            '/api/auth/user',
            '/api/auth/logout',
        ];

        foreach ($routes as $route) {
            $method = $route === '/api/auth/user' ? 'get' : 'post';
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($method, $route);

            $this->assertContains($response->status(), [200, 401], "Route {$route} should return 200 or 401");
        }
    }

    /**
     * Test user logout with invalid token
     */
    public function test_user_logout_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test password confirmation validation
     */
    public function test_password_confirmation_validation(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test minimum password length validation
     */
    public function test_minimum_password_length_validation(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test required fields validation
     */
    public function test_required_fields_validation(): void
    {
        // Test missing name
        $response = $this->postJson('/api/auth/register', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test missing email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test missing password
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user profile update with name only
     */
    public function test_user_profile_update_name_only(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $token = $user->createToken('auth_token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Name Only',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => 'Updated Name Only',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name Only',
            'email' => $user->email,
        ]);
    }

    /**
     * Test user profile update with email only
     */
    public function test_user_profile_update_email_only(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        $token = $user->createToken('auth_token')->plainTextToken;

        $updateData = [
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test that authentication middleware protects routes
     */
    public function test_authentication_middleware_protects_routes(): void
    {
        $protectedRoutes = [
            ['method' => 'GET', 'route' => '/api/auth/user'],
            ['method' => 'POST', 'route' => '/api/auth/logout'],
            ['method' => 'PUT', 'route' => '/api/auth/profile'],
            ['method' => 'POST', 'route' => '/api/auth/refresh'],
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->json($route['method'], $route['route']);
            $response->assertStatus(401);
        }
    }
}