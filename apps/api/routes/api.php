<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Icd10Controller;
use App\Http\Controllers\Api\V1\PatientAuthController;
use App\Http\Controllers\Api\V1\PublicQueueController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\RmeController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', HealthController::class);

// Staff auth
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

// Protected v1 routes
Route::prefix('v1')->middleware(['api', 'auth:sanctum', 'clinic'])->group(function (): void {
    // Queue management
    Route::prefix('queue')->group(function (): void {
        Route::get('/', [QueueController::class, 'index']);
        Route::post('/register', [QueueController::class, 'register'])->middleware('feature:walk_in');
        Route::post('/online', [QueueController::class, 'online'])->middleware('feature:online_queue');
        Route::patch('/{id}/call', [QueueController::class, 'call']);
        Route::patch('/{id}/complete', [QueueController::class, 'complete']);
        Route::patch('/{id}/transfer', [QueueController::class, 'transfer']);
        Route::post('/{id}/vital-signs', [QueueController::class, 'vitalSigns']);
    });

    // RME — specific routes before wildcard
    Route::prefix('rme')->group(function (): void {
        Route::post('/', [RmeController::class, 'store']);
        Route::get('/patient/{patientId}', [RmeController::class, 'index']);
        Route::get('/{id}', [RmeController::class, 'show']);
    });

    // ICD-10 search
    Route::get('/icd10/search', [Icd10Controller::class, 'search']);
});

// Public routes (no auth)
Route::prefix('public')->group(function (): void {
    Route::get('/queue/{token}', [PublicQueueController::class, 'track']);
});
