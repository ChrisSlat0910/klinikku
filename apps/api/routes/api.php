<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PatientAuthController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', HealthController::class);

// Staff auth (no clinic scope needed for login)
Route::prefix('v1/auth')->middleware('api')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Patient auth
Route::prefix('v1/auth/patient')->middleware('api')->group(function (): void {
    Route::post('/register', [PatientAuthController::class, 'register']);
    Route::post('/login', [PatientAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/google', [PatientAuthController::class, 'google']);
});

// Protected v1 routes with clinic scope
Route::prefix('v1')->middleware(['api', 'auth:sanctum', 'clinic'])->group(function (): void {
    // Domain endpoints will be added in subsequent steps
});

// Public routes (no auth)
Route::prefix('public')->group(function (): void {
    // Public queue tracking will be added in Step 27
});
