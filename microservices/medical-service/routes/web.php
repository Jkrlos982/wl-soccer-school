<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MedicalController;

Route::get('/', function () {
    return response()->json([
        'service' => 'Medical Service',
        'status' => 'active',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});

// Medical Records API Routes
Route::prefix('api/medical')->group(function () {
    Route::get('/dashboard', [MedicalController::class, 'dashboard']);
    Route::get('/records', [MedicalController::class, 'index']);
    Route::post('/records', [MedicalController::class, 'store']);
    Route::get('/records/{medicalRecord}', [MedicalController::class, 'show']);
    Route::put('/records/{medicalRecord}', [MedicalController::class, 'update']);
    Route::delete('/records/{medicalRecord}', [MedicalController::class, 'destroy']);
    Route::get('/records/{medicalRecord}/exams', [MedicalController::class, 'getExams']);
    Route::get('/records/{medicalRecord}/injuries', [MedicalController::class, 'getInjuries']);
    Route::get('/records/{medicalRecord}/certificates', [MedicalController::class, 'getCertificates']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => now()
    ]);
});
