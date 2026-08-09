<?php

use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Icd10Controller;
use App\Http\Controllers\Api\V1\LabController;
use App\Http\Controllers\Api\V1\PatientAuthController;
use App\Http\Controllers\Api\V1\PharmacyController;
use App\Http\Controllers\Api\V1\PublicQueueController;
use App\Http\Controllers\Api\V1\PublicRujukanController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RmeController;
use App\Http\Controllers\Api\V1\RujukanController;
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

    // RME
    Route::prefix('rme')->group(function (): void {
        Route::post('/', [RmeController::class, 'store']);
        Route::get('/patient/{patientId}', [RmeController::class, 'index']);
        Route::get('/{id}', [RmeController::class, 'show']);
    });

    // ICD-10 search
    Route::get('/icd10/search', [Icd10Controller::class, 'search']);

    // Appointments
    Route::prefix('appointments')->middleware('feature:queue_appointment')->group(function (): void {
        Route::get('/slots', [AppointmentController::class, 'slots']);
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::patch('/{id}/cancel', [AppointmentController::class, 'cancel']);
    });

    // Rujukan digital
    Route::prefix('referral')->group(function (): void {
        Route::get('/', [RujukanController::class, 'index']);
        Route::post('/', [RujukanController::class, 'store']);
    });

    // Pharmacy
    Route::prefix('pharmacy')->group(function (): void {
        Route::get('/prescriptions', [PharmacyController::class, 'prescriptions']);
        Route::patch('/prescriptions/{id}/dispense', [PharmacyController::class, 'dispense']);
        Route::get('/drugs', [PharmacyController::class, 'drugs']);
        Route::post('/drugs', [PharmacyController::class, 'createDrug']);
        Route::patch('/drugs/{id}/stock', [PharmacyController::class, 'adjustStock']);
    });

    // Billing
    Route::prefix('billing')->group(function (): void {
        Route::get('/pending', [BillingController::class, 'pending']);
        Route::get('/history', [BillingController::class, 'history']);
        Route::get('/{queueItemId}', [BillingController::class, 'show']);
        Route::post('/{queueItemId}/pay', [BillingController::class, 'pay']);
    });

    // Lab
    Route::prefix('lab')->middleware('feature:lab')->group(function (): void {
        Route::get('/orders', [LabController::class, 'index']);
        Route::post('/orders', [LabController::class, 'store']);
        Route::post('/orders/{id}/results', [LabController::class, 'submitResult']);
    });

    // Reports
    Route::prefix('reports')->group(function (): void {
        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/bpjs', [ReportController::class, 'bpjs'])->middleware('feature:bpjs_reporting');
        Route::get('/export', [ReportController::class, 'export']);
    });

    // AI assistant
    Route::post('/ai/generate', [AiController::class, 'generate']);
});

// Public routes (no auth)
Route::prefix('public')->group(function (): void {
    Route::get('/queue/{token}', [PublicQueueController::class, 'track']);
    Route::get('/referral/verify/{code}', [PublicRujukanController::class, 'verify']);
});
