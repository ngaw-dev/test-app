<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected SupabaseAuthService $supabaseAuth;

    public function __construct(SupabaseAuthService $supabaseAuth)
    {
        $this->supabaseAuth = $supabaseAuth;
        $this->middleware('auth:sanctum')->except(['login', 'register', 'forgotPassword']);
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sign up with Supabase
        $supabaseResult = $this->supabaseAuth->signUp(
            $request->email,
            $request->password,
            ['name' => $request->name]
        );

        if (!$supabaseResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Supabase registration failed',
                'error' => $supabaseResult['error'],
            ], 422);
        }

        // Create local user record
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'supabase_id' => $supabaseResult['user']['id'] ?? null,
            'email_verified_at' => $supabaseResult['user']['email_confirmed_at'] ?? null,
        ]);

        // Create sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'supabase_user' => $supabaseResult['user'],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Authenticate with Supabase
        $supabaseResult = $this->supabaseAuth->signIn(
            $request->email,
            $request->password
        );

        if (!$supabaseResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => $supabaseResult['error'],
            ], 401);
        }

        // Find or create local user
        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => $supabaseResult['user']['user_metadata']['name'] ?? 'User',
                'password' => Hash::make($request->password), // Store hashed password
                'supabase_id' => $supabaseResult['user']['id'] ?? null,
                'email_verified_at' => $supabaseResult['user']['email_confirmed_at'] ?? null,
            ]
        );

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'supabase_user' => $supabaseResult['user'],
            'access_token' => $token,
            'supabase_access_token' => $supabaseResult['access_token'],
            'supabase_refresh_token' => $supabaseResult['refresh_token'],
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get fresh user data from Supabase
        $supabaseToken = $request->header('X-Supabase-Token');
        if ($supabaseToken) {
            $supabaseResult = $this->supabaseAuth->getUser($supabaseToken);
            if ($supabaseResult['success']) {
                $user->update([
                    'email_verified_at' => $supabaseResult['user']['email_confirmed_at'] ?? $user->email_verified_at,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Sign out from Supabase if token provided
        $supabaseToken = $request->header('X-Supabase-Token');
        if ($supabaseToken) {
            $this->supabaseAuth->signOut($supabaseToken);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $supabaseResult = $this->supabaseAuth->refreshToken($request->refresh_token);

        if (!$supabaseResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $supabaseResult['error'],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'access_token' => $supabaseResult['access_token'],
            'refresh_token' => $supabaseResult['refresh_token'],
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $supabaseResult = $this->supabaseAuth->resetPassword($request->email);

        if (!$supabaseResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $supabaseResult['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset email sent',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $updateData = $request->only(['name', 'email']);

        // Update in Supabase if token provided
        $supabaseToken = $request->header('X-Supabase-Token');
        if ($supabaseToken && !empty($updateData)) {
            $attributes = [];
            if (isset($updateData['name'])) {
                $attributes['data'] = ['name' => $updateData['name']];
            }
            if (isset($updateData['email'])) {
                $attributes['email'] = $updateData['email'];
            }

            if (!empty($attributes)) {
                $this->supabaseAuth->updateUser($supabaseToken, $attributes);
            }
        }

        // Update local user
        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }
}