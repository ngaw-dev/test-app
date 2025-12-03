<?php

namespace Tests\Unit;

use App\Services\SupabaseAuthService;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\env;
use Illuminate\Support\Facades\Log;

class SupabaseAuthServiceTest extends TestCase
{
    protected SupabaseAuthService $supabaseService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock environment variables
        env(['SUPABASE_URL' => 'https://test.supabase.co']);
        env(['SUPABASE_PUBLISHABLE_KEY' => 'test-key']);
        env(['JWT_SECRET' => 'test-secret']);

        $this->supabaseService = new SupabaseAuthService();
    }

    /**
     * Test service initialization
     */
    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SupabaseAuthService::class, $this->supabaseService);
    }

    /**
     * Test validate token method
     */
    public function test_validate_token_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'validateToken'),
            'validateToken method should exist'
        );
    }

    /**
     * Test sign up method
     */
    public function test_sign_up_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'signUp'),
            'signUp method should exist'
        );
    }

    /**
     * Test sign in method
     */
    public function test_sign_in_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'signIn'),
            'signIn method should exist'
        );
    }

    /**
     * Test get user method
     */
    public function test_get_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'getUser'),
            'getUser method should exist'
        );
    }

    /**
     * Test sign out method
     */
    public function test_sign_out_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'signOut'),
            'signOut method should exist'
        );
    }

    /**
     * Test refresh token method
     */
    public function test_refresh_token_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'refreshToken'),
            'refreshToken method should exist'
        );
    }

    /**
     * Test reset password method
     */
    public function test_reset_password_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'resetPassword'),
            'resetPassword method should exist'
        );
    }

    /**
     * Test update user method
     */
    public function test_update_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists($this->supabaseService, 'updateUser'),
            'updateUser method should exist'
        );
    }

    /**
     * Test successful sign up
     */
    public function test_successful_sign_up(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'user' => [
                    'id' => 'test-uuid',
                    'email' => 'test@example.com',
                    'email_confirmed_at' => now(),
                ],
                'session' => [
                    'access_token' => 'test-access-token',
                    'refresh_token' => 'test-refresh-token',
                ],
            ])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->signUp('test@example.com', 'password123', ['name' => 'Test User']);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-uuid', $result['user']['id']);
        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertEquals('test-access-token', $result['access_token']);
    }

    /**
     * Test failed sign up
     */
    public function test_failed_sign_up(): void
    {
        $mockHandler = new MockHandler([
            new RequestException('Error', new Request('POST', 'test'), new Response(400, [], json_encode([
                'error_description' => 'Email already registered',
            ]))),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        Log::shouldReceive('error')->once();

        $result = $this->supabaseService->signUp('existing@example.com', 'password123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Email already registered', $result['error']);
    }

    /**
     * Test successful sign in
     */
    public function test_successful_sign_in(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'user' => [
                    'id' => 'test-uuid',
                    'email' => 'test@example.com',
                    'user_metadata' => ['name' => 'Test User'],
                ],
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
            ])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->signIn('test@example.com', 'password123');

        $this->assertTrue($result['success']);
        $this->assertEquals('test-uuid', $result['user']['id']);
        $this->assertEquals('test-access-token', $result['access_token']);
        $this->assertEquals('test-refresh-token', $result['refresh_token']);
    }

    /**
     * Test successful get user
     */
    public function test_successful_get_user(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'test-uuid',
                'email' => 'test@example.com',
                'email_confirmed_at' => now(),
                'user_metadata' => ['name' => 'Test User'],
            ])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->getUser('test-access-token');

        $this->assertTrue($result['success']);
        $this->assertEquals('test-uuid', $result['user']['id']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    /**
     * Test successful sign out
     */
    public function test_successful_sign_out(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->signOut('test-access-token');

        $this->assertTrue($result['success']);
    }

    /**
     * Test successful refresh token
     */
    public function test_successful_refresh_token(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'user' => [
                    'id' => 'test-uuid',
                    'email' => 'test@example.com',
                ],
            ])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->refreshToken('test-refresh-token');

        $this->assertTrue($result['success']);
        $this->assertEquals('new-access-token', $result['access_token']);
        $this->assertEquals('new-refresh-token', $result['refresh_token']);
    }

    /**
     * Test successful reset password
     */
    public function test_successful_reset_password(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->resetPassword('test@example.com');

        $this->assertTrue($result['success']);
    }

    /**
     * Test successful update user
     */
    public function test_successful_update_user(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'test-uuid',
                'email' => 'test@example.com',
                'user_metadata' => ['name' => 'Updated Name'],
            ])),
        ]);

        $client = new Client(['handler' => $mockHandler]);
        $this->supabaseService = new class($client) extends SupabaseAuthService {
            public function __construct(Client $client)
            {
                $this->client = $client;
                $this->supabaseUrl = 'https://test.supabase.co';
                $this->supabaseKey = 'test-key';
                $this->jwtSecret = 'test-secret';
            }
        };

        $result = $this->supabaseService->updateUser('test-access-token', ['data' => ['name' => 'Updated Name']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Updated Name', $result['user']['user_metadata']['name']);
    }

    /**
     * Test validate token with valid JWT
     */
    public function test_validate_token_with_valid_jwt(): void
    {
        // Create a valid JWT token for testing
        $payload = [
            'sub' => 'test-uuid',
            'email' => 'test@example.com',
            'exp' => time() + 3600,
        ];

        $token = \Firebase\JWT\JWT::encode($payload, 'test-secret', 'HS256');

        $result = $this->supabaseService->validateToken($token);

        $this->assertIsArray($result);
        $this->assertEquals('test-uuid', $result['sub']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    /**
     * Test validate token with invalid JWT
     */
    public function test_validate_token_with_invalid_jwt(): void
    {
        Log::shouldReceive('error')->once();

        $result = $this->supabaseService->validateToken('invalid-token');

        $this->assertNull($result);
    }

    /**
     * Test methods return expected array structure
     */
    public function test_methods_return_expected_structure(): void
    {
        // Test that methods return arrays with success/error structure
        $testCases = [
            'signUp' => ['email' => 'test@example.com', 'password' => 'password123'],
            'signIn' => ['email' => 'test@example.com', 'password' => 'password123'],
            'getUser' => ['test-access-token'],
            'signOut' => ['test-access-token'],
            'refreshToken' => ['test-refresh-token'],
            'resetPassword' => ['test@example.com'],
            'updateUser' => ['test-access-token', ['data' => ['name' => 'Test']]],
        ];

        foreach ($testCases as $method => $args) {
            if (method_exists($this->supabaseService, $method)) {
                $result = $this->supabaseService->{$method}(...$args);

                $this->assertIsArray($result, "$method should return an array");
                $this->assertArrayHasKey('success', $result, "$method should have 'success' key");
            }
        }
    }
}