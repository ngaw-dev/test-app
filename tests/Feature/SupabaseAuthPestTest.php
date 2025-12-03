<?php

use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Support\Facades\Hash;

uses()->group('supabase-auth');

beforeEach(function () {
    $this->supabase = Mockery::mock(SupabaseAuthService::class);
    $this->app->instance(SupabaseAuthService::class, $this->supabase);
});

it('registers a user through Supabase and returns auth payload', function () {
    $this->supabase
        ->shouldReceive('signUp')
        ->once()
        ->with('jane@example.com', 'password123', ['name' => 'Jane Doe'])
        ->andReturn([
            'success' => true,
            'user' => [
                'id' => 'supabase-user-id',
                'email_confirmed_at' => now()->toISOString(),
            ],
            'session' => ['access_token' => 'supabase-access-token'],
            'access_token' => 'supabase-access-token',
        ]);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User registered successfully')
        ->assertJsonPath('supabase_user.id', 'supabase-user-id')
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'name', 'email'],
            'supabase_user',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
        'supabase_id' => 'supabase-user-id',
    ]);
});

it('rejects login when Supabase denies the credentials', function () {
    $this->supabase
        ->shouldReceive('signIn')
        ->once()
        ->with('invalid@example.com', 'bad-password')
        ->andReturn([
            'success' => false,
            'error' => 'Invalid credentials',
        ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'invalid@example.com',
        'password' => 'bad-password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid credentials');
});

it('logs in a user with Supabase metadata and issues tokens', function () {
    $this->supabase
        ->shouldReceive('signIn')
        ->once()
        ->with('login@example.com', 'secret123')
        ->andReturn([
            'success' => true,
            'user' => [
                'id' => 'supabase-login-id',
                'user_metadata' => ['name' => 'Meta Name'],
                'email_confirmed_at' => now()->toISOString(),
            ],
            'access_token' => 'supabase-access',
            'refresh_token' => 'supabase-refresh',
        ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.email', 'login@example.com')
        ->assertJsonPath('user.name', 'Meta Name')
        ->assertJsonPath('supabase_access_token', 'supabase-access')
        ->assertJsonPath('supabase_refresh_token', 'supabase-refresh');

    $this->assertDatabaseHas('users', [
        'email' => 'login@example.com',
        'supabase_id' => 'supabase-login-id',
    ]);
});

it('pushes profile updates to Supabase when a token is provided', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'password' => Hash::make('password123'),
    ]);

    $this->supabase
        ->shouldReceive('updateUser')
        ->once()
        ->with('supabase-jwt', ['data' => ['name' => 'New Name']])
        ->andReturn([
            'success' => true,
            'user' => ['id' => 'supabase-id'],
        ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Supabase-Token' => 'supabase-jwt'])
        ->putJson('/api/auth/profile', ['name' => 'New Name']);

    $response->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully')
        ->assertJsonPath('user.name', 'New Name');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});
