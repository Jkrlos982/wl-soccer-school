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
    // Dashboard and statistics
    Route::get('/dashboard', [MedicalController::class, 'dashboard']);
    Route::get('/attention-required', [MedicalController::class, 'getAttentionRequired']);
    
    // Medical Records CRUD
    Route::get('/records', [MedicalController::class, 'index']);
    Route::post('/records', [MedicalController::class, 'store']);
    Route::get('/records/{medicalRecord}', [MedicalController::class, 'show']);
    Route::put('/records/{medicalRecord}', [MedicalController::class, 'update']);
    Route::delete('/records/{medicalRecord}', [MedicalController::class, 'destroy']);
    
    // Medical Record relationships
    Route::get('/records/{medicalRecord}/exams', [MedicalController::class, 'getExams']);
    Route::get('/records/{medicalRecord}/injuries', [MedicalController::class, 'getInjuries']);
    Route::get('/records/{medicalRecord}/certificates', [MedicalController::class, 'getCertificates']);
    
    // Medical Exams
    Route::post('/records/{recordId}/exams', [MedicalController::class, 'scheduleExam']);
    Route::put('/exams/{examId}/complete', [MedicalController::class, 'completeExam']);
    
    // Injuries
    Route::post('/records/{recordId}/injuries', [MedicalController::class, 'recordInjury']);
    Route::put('/injuries/{injuryId}/status', [MedicalController::class, 'updateInjuryStatus']);
    
    // Medical Certificates
    Route::post('/records/{recordId}/certificates', [MedicalController::class, 'generateCertificate']);
    Route::get('/records/{recordId}/clearance', [MedicalController::class, 'validateClearance']);
    
    // Player-specific routes
    Route::get('/players/{playerId}/records', [MedicalController::class, 'getPlayerRecords']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => now()
    ]);
});
