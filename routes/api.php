<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DummyModelController;
use App\Http\Controllers\Auth\SupabaseAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Laravel Sanctum based authentication (existing)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
});

// Supabase based authentication (new)
Route::prefix('supabase/auth')->group(function () {
    Route::post('register', [SupabaseAuthController::class, 'register']);
    Route::post('login', [SupabaseAuthController::class, 'login']);
    Route::post('logout', [SupabaseAuthController::class, 'logout'])->middleware('supabase.auth');
    Route::get('me', [SupabaseAuthController::class, 'me'])->middleware('supabase.auth');
    Route::post('forgot-password', [SupabaseAuthController::class, 'forgotPassword']);
    Route::put('profile', [SupabaseAuthController::class, 'updateProfile'])->middleware('supabase.auth');
});

Route::apiResource('dummy-models', DummyModelController::class);
