<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    private string $url;
    private string $key;
    private string $jwtSecret;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
        $this->jwtSecret = config('services.supabase.jwt_secret');
    }

    /**
     * Create a new user in Supabase Auth
     */
    public function createUser(array $userData): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
            'Content-Type' => 'application/json',
        ])->post("{$this->url}/auth/v1/admin/users", $userData);

        if ($response->failed()) {
            Log::error('Supabase user creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to create user in Supabase');
        }

        return $response->json();
    }

    /**
     * Authenticate user with Supabase
     */
    public function authenticate(string $email, string $password): array
    {
        $response = Http::asJson()->post("{$this->url}/auth/v1/token?grant_type=password", [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response->failed()) {
            Log::error('Supabase authentication failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception('Authentication failed');
        }

        return $response->json();
    }

    /**
     * Get user by ID
     */
    public function getUser(string $userId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
        ])->get("{$this->url}/auth/v1/admin/users/{$userId}");

        if ($response->failed()) {
            Log::error('Failed to fetch user from Supabase', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to fetch user');
        }

        return $response->json();
    }

    /**
     * Update user
     */
    public function updateUser(string $userId, array $userData): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
            'Content-Type' => 'application/json',
        ])->put("{$this->url}/auth/v1/admin/users/{$userId}", $userData);

        if ($response->failed()) {
            Log::error('Failed to update user in Supabase', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to update user');
        }

        return $response->json();
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
        ])->delete("{$this->url}/auth/v1/admin/users/{$userId}");

        if ($response->failed()) {
            Log::error('Failed to delete user from Supabase', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to delete user');
        }

        return $response->successful();
    }

    /**
     * Verify JWT token
     */
    public function verifyToken(string $token): ?array
    {
        try {
            // For production, you should properly decode and verify the JWT
            // This is a simplified version that calls Supabase to verify
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'apikey' => $this->key,
            ])->get("{$this->url}/auth/v1/user");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Token verification failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(string $email): bool
    {
        $response = Http::asJson()->post("{$this->url}/auth/v1/recover", [
            'email' => $email,
        ]);

        return $response->successful();
    }

    /**
     * Get REST client for database operations
     */
    public function getRestClient()
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->baseUrl("{$this->url}/rest/v1");
    }
}