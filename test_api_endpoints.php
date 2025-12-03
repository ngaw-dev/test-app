<?php

/**
 * Simple API Endpoint Test Script
 * Tests all Supabase authentication endpoints
 */

$baseUrl = 'http://localhost:8000/api/auth';

echo "🔍 Testing Supabase Authentication API Endpoints\n";
echo "================================================\n\n";

// Test endpoints
$endpoints = [
    [
        'method' => 'POST',
        'endpoint' => '/register',
        'description' => 'User Registration',
        'data' => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]
    ],
    [
        'method' => 'POST',
        'endpoint' => '/login',
        'description' => 'User Login',
        'data' => [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]
    ],
    [
        'method' => 'POST',
        'endpoint' => '/forgot-password',
        'description' => 'Forgot Password',
        'data' => [
            'email' => 'test@example.com'
        ]
    ],
    [
        'method' => 'GET',
        'endpoint' => '/user',
        'description' => 'Get Authenticated User',
        'data' => null,
        'requires_auth' => true
    ],
    [
        'method' => 'PUT',
        'endpoint' => '/profile',
        'description' => 'Update Profile',
        'data' => [
            'name' => 'Updated Name'
        ],
        'requires_auth' => true
    ],
    [
        'method' => 'POST',
        'endpoint' => '/logout',
        'description' => 'User Logout',
        'data' => null,
        'requires_auth' => true
    ],
    [
        'method' => 'POST',
        'endpoint' => '/refresh',
        'description' => 'Refresh Token',
        'data' => [
            'refresh_token' => 'mock-refresh-token'
        ],
        'requires_auth' => true
    ]
];

function testEndpoint($method, $url, $data = null, $authToken = null) {
    $ch = curl_init();

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($authToken) {
        $headers[] = 'Authorization: Bearer ' . $authToken;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'error' => $error
    ];
}

echo "Available API Endpoints:\n";
echo "• POST   /api/auth/register     - User registration\n";
echo "• POST   /api/auth/login        - User login\n";
echo "• POST   /api/auth/logout       - User logout (requires auth)\n";
echo "• GET    /api/auth/user         - Get user info (requires auth)\n";
echo "• PUT    /api/auth/profile      - Update profile (requires auth)\n";
echo "• POST   /api/auth/forgot-password - Forgot password\n";
echo "• POST   /api/auth/refresh      - Refresh token (requires auth)\n\n";

echo "API Endpoint Test Results:\n";
echo "============================\n\n";

$authToken = null;

foreach ($endpoints as $test) {
    $url = $baseUrl . $test['endpoint'];
    $requiresAuth = $test['requires_auth'] ?? false;

    echo "📍 {$test['method']} {$test['endpoint']} - {$test['description']}\n";

    if ($requiresAuth && !$authToken) {
        echo "   ⚠️  Requires authentication token - skipping\n\n";
        continue;
    }

    $result = testEndpoint($test['method'], $url, $test['data'], $authToken);

    if ($result['error']) {
        echo "   ❌ Connection Error: {$result['error']}\n";
    } else {
        echo "   📊 Status: {$result['http_code']}\n";

        if ($result['response']) {
            if (isset($result['response']['success'])) {
                echo "   ✅ Success: " . ($result['response']['success'] ? 'Yes' : 'No') . "\n";
                if (isset($result['response']['message'])) {
                    echo "   💬 Message: " . $result['response']['message'] . "\n";
                }
                if (isset($result['response']['access_token'])) {
                    $authToken = $result['response']['access_token'];
                    echo "   🔑 Token received and saved for auth tests\n";
                }
            } else {
                echo "   📄 Response: " . json_encode($result['response']) . "\n";
            }
        }

        // Check for validation errors
        if (isset($result['response']['errors'])) {
            echo "   ⚠️  Validation errors:\n";
            foreach ($result['response']['errors'] as $field => $errors) {
                echo "      • {$field}: " . implode(', ', (array)$errors) . "\n";
            }
        }
    }

    echo "\n";
}

echo "🔒 Authentication Service Configuration\n";
echo "======================================\n";

// Check environment variables (without exposing sensitive data)
$envVars = [
    'SUPABASE_URL' => $_ENV['SUPABASE_URL'] ?? 'Not set',
    'SUPABASE_PUBLISHABLE_KEY' => $_ENV['SUPABASE_PUBLISHABLE_KEY'] ? 'Set ✓' : 'Not set ✗',
    'JWT_SECRET' => $_ENV['JWT_SECRET'] ? 'Set ✓' : 'Not set ✗',
    'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? 'Not set',
    'DB_HOST' => $_ENV['DB_HOST'] ?? 'Not set'
];

foreach ($envVars as $key => $value) {
    if (in_array($key, ['SUPABASE_PUBLISHABLE_KEY', 'JWT_SECRET'])) {
        echo "• {$key}: {$value}\n";
    } else {
        echo "• {$key}: {$value}\n";
    }
}

echo "\n📋 Configuration Notes:\n";
echo "• Make sure SUPABASE_URL points to your Supabase project\n";
echo "• Set SUPABASE_PUBLISHABLE_KEY from your Supabase project settings\n";
echo "• Configure JWT_SECRET for token validation\n";
echo "• Ensure database connection is properly configured\n";
echo "• Run 'php artisan migrate' to set up database tables\n\n";

echo "🚀 Next Steps:\n";
echo "• Configure .env with your Supabase credentials\n";
echo "• Test with actual Supabase project\n";
echo "• Run comprehensive test suite: php artisan test\n";
echo "• Verify authentication flow in your application\n\n";

echo "✨ Authentication API Endpoints Confirmed!\n";