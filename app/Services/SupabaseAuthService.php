<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SupabaseAuthService
{
    protected Client $client;
    protected string $supabaseUrl;
    protected string $supabaseKey;
    protected string $jwtSecret;

    public function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->supabaseKey = env('SUPABASE_PUBLISHABLE_KEY');
        $this->jwtSecret = env('JWT_SECRET');

        $this->client = new Client([
            'base_uri' => $this->supabaseUrl,
            'headers' => [
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function signUp(string $email, string $password, array $metadata = []): array
    {
        try {
            $response = $this->client->post('/auth/v1/signup', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'data' => $metadata,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'user' => $data['user'] ?? null,
                'session' => $data['session'] ?? null,
                'access_token' => $data['session']['access_token'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Supabase signup error', [
                'message' => $e->getMessage(),
                'email' => $email,
            ]);

            return [
                'success' => false,
                'error' => $this->parseError($e),
            ];
        }
    }

    public function signIn(string $email, string $password): array
    {
        try {
            $response = $this->client->post('/auth/v1/token?grant_type=password', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'user' => $data['user'] ?? null,
                'session' => $data['session'] ?? null,
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Supabase signin error', [
                'message' => $e->getMessage(),
                'email' => $email,
            ]);

            return [
                'success' => false,
                'error' => $this->parseError($e),
            ];
        }
    }

    public function getUser(string $accessToken): array
    {
        try {
            $response = $this->client->get('/auth/v1/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $user = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Supabase get user error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->parseError($e),
            ];
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = $this->client->post('/auth/v1/token?grant_type=refresh_token', [
                'json' => [
                    'refresh_token' => $refreshToken,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'user' => $data['user'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Supabase refresh token error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->parseError($e),
            ];
        }
    }

    public function resetPassword(string $email): array
    {
        try {
            $this->client->post('/auth/v1/recover', [
                'json' => [
                    'email' => $email,
                ],
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Supabase reset password error', [
                'message' => $e->getMessage(),
                'email' => $email,
            ]);

            return [
                'success' => false,
                'error' => $this->parseError($e),
            ];
        }
    }

    public function validateToken(string $token): ?array
    {
        try {
            $token = str_replace('Bearer ', '', $token);
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array) $payload;
        } catch (\Exception $e) {
            Log::error('Supabase token validation error', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function parseError(\Exception $e): string
    {
        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            return $data['error_description'] ?? $data['msg'] ?? $e->getMessage();
        }
        return $e->getMessage();
    }
}