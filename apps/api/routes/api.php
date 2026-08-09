<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

// v1 routes — protected by clinic scope middleware
Route::prefix('v1')->middleware(['api', 'auth:sanctum', 'clinic'])->group(function (): void {
    // Auth routes will be added in Step 26
});

// Public routes — no auth required
Route::prefix('public')->group(function (): void {
    // Public queue tracking will be added in Step 27
});
