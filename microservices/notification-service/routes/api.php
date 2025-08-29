<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\EmailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// WhatsApp Business API Routes
Route::prefix('whatsapp')->group(function () {
    // Webhook endpoint (no auth required for WhatsApp webhooks)
    Route::match(['get', 'post'], '/webhook', [WhatsAppController::class, 'webhook'])
        ->name('whatsapp.webhook');
    
    // Status endpoint (no auth for monitoring)
    Route::get('/status', [WhatsAppController::class, 'status'])
        ->name('whatsapp.status');
    
    // Protected endpoints for testing and management
    Route::middleware(['auth:sanctum'])->group(function () {
        // Send test message
        Route::post('/send/test', [WhatsAppController::class, 'sendTest'])
            ->name('whatsapp.send.test');
        
        // Send template message
        Route::post('/send/template', [WhatsAppController::class, 'sendTemplate'])
            ->name('whatsapp.send.template');
        
        // Send media message
        Route::post('/send/media', [WhatsAppController::class, 'sendMedia'])
            ->name('whatsapp.send.media');
        
        // Send interactive message
        Route::post('/send/interactive', [WhatsAppController::class, 'sendInteractive'])
            ->name('whatsapp.send.interactive');
        
        // Mark message as read
        Route::post('/mark-read', [WhatsAppController::class, 'markAsRead'])
            ->name('whatsapp.mark.read');
    });
});

// Email Service Routes
Route::prefix('email')->group(function () {
    // Status endpoint (no auth for monitoring)
    Route::get('/status', [EmailController::class, 'getStatus'])
        ->name('email.status');
    
    // Email validation endpoint (no auth for basic validation)
    Route::post('/validate', [EmailController::class, 'validateEmail'])
        ->name('email.validate');
    
    // Protected endpoints for email management
    Route::middleware(['auth:sanctum'])->group(function () {
        // Send transactional email
        Route::post('/send/transactional', [EmailController::class, 'sendTransactional'])
            ->name('email.send.transactional');
        
        // Send templated email
        Route::post('/send/templated', [EmailController::class, 'sendTemplated'])
            ->name('email.send.templated');
        
        // Send bulk emails
        Route::post('/send/bulk', [EmailController::class, 'sendBulk'])
            ->name('email.send.bulk');
        
        // Send bulk templated emails
        Route::post('/send/bulk-templated', [EmailController::class, 'sendBulkTemplated'])
            ->name('email.send.bulk.templated');
        
        // Preview email template
        Route::post('/preview', [EmailController::class, 'previewTemplate'])
            ->name('email.preview');
        
        // Create email template
        Route::post('/template/create', [EmailController::class, 'createTemplate'])
            ->name('email.template.create');
        
        // Get email statistics
        Route::get('/statistics', [EmailController::class, 'getStatistics'])
            ->name('email.statistics');
        
        // Test email configuration
        Route::post('/test', [EmailController::class, 'testConfiguration'])
            ->name('email.test');
    });
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'service' => 'Notification Service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
})->name('health');