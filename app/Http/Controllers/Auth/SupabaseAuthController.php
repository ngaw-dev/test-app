<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SupabaseAuthController extends Controller
{
    public function __construct(private SupabaseService $supabaseService)
    {
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $userData = [
                'email' => $request->email,
                'password' => $request->password,
                'email_confirm' => true,
            ];

            if ($request->has('data')) {
                $userData['user_metadata'] = $request->data;
            }

            $user = $this->supabaseService->createUser($userData);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Authenticate user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $authData = $this->supabaseService->authenticate(
                $request->email,
                $request->password
            );

            return response()->json([
                'message' => 'Authentication successful',
                'access_token' => $authData['access_token'],
                'refresh_token' => $authData['refresh_token'] ?? null,
                'user' => $authData['user'] ?? null,
                'expires_in' => $authData['expires_in'] ?? 3600,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Logout user (client-side token removal)
     */
    public function logout(): JsonResponse
    {
        // In a real implementation, you might want to revoke the token
        // Supabase doesn't have a built-in token revocation API
        // So token removal is primarily client-side

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Request password reset
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $success = $this->supabaseService->resetPassword($request->email);

            if ($success) {
                return response()->json([
                    'message' => 'Password reset email sent',
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to send password reset email',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process password reset',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updatedUser = $this->supabaseService->updateUser(
                $user->id,
                ['user_metadata' => $request->data]
            );

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $updatedUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}