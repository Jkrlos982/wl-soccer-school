<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinancialConceptController;
use App\Http\Controllers\ConceptTemplateController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'financial-service',
        'timestamp' => now()->toISOString(),
        'hot_reload' => 'enabled'
    ]);
});

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Financial Concepts - Public endpoints
    Route::get('/financial-concepts/categories', [FinancialConceptController::class, 'getCategories']);
    Route::get('/financial-concepts/types', [FinancialConceptController::class, 'getTypes']);
    
    // Concept Templates - Public endpoints
    Route::get('/concept-templates/system', [ConceptTemplateController::class, 'getSystemTemplates']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // Financial Concepts Management
    Route::apiResource('financial-concepts', FinancialConceptController::class);
    Route::prefix('financial-concepts')->group(function () {
        Route::get('/defaults', [FinancialConceptController::class, 'getDefaults']);
        Route::get('/by-type/{type}', [FinancialConceptController::class, 'getByType']);
        Route::get('/by-category/{category}', [FinancialConceptController::class, 'getByCategory']);
        Route::post('/{id}/toggle-status', [FinancialConceptController::class, 'toggleStatus']);
        Route::post('/bulk-create', [FinancialConceptController::class, 'bulkCreate']);
        Route::delete('/bulk-delete', [FinancialConceptController::class, 'bulkDelete']);
    });
    
    // Concept Templates Management
    Route::apiResource('concept-templates', ConceptTemplateController::class);
    Route::prefix('concept-templates')->group(function () {
        Route::get('/active', [ConceptTemplateController::class, 'getActive']);
        Route::post('/{id}/duplicate', [ConceptTemplateController::class, 'duplicate']);
        Route::post('/{id}/create-concept', [ConceptTemplateController::class, 'createConcept']);
        Route::post('/{id}/toggle-status', [ConceptTemplateController::class, 'toggleStatus']);
    });
    
    // Transactions Management
    Route::apiResource('transactions', TransactionController::class);
    Route::prefix('transactions')->group(function () {
        // Status management (approval workflow)
        Route::patch('/{id}/status', [TransactionController::class, 'updateStatus']);
        
        // Statistics and reporting
        Route::get('/statistics', [TransactionController::class, 'statistics']);
        
        // Reference number generation
        Route::post('/generate-reference', [TransactionController::class, 'generateReferenceNumber']);
        
        // Legacy endpoints (for backward compatibility)
        Route::get('/by-concept/{conceptId}', [TransactionController::class, 'getByFinancialConcept']);
        Route::get('/by-date-range', [TransactionController::class, 'getByDateRange']);
        Route::get('/summary', [TransactionController::class, 'getSummary']);
        Route::post('/bulk-create', [TransactionController::class, 'bulkCreate']);
    });
    
    // Accounts Management
    Route::apiResource('accounts', AccountController::class);
    Route::prefix('accounts')->group(function () {
        Route::get('/balance/{id}', [AccountController::class, 'getBalance']);
        Route::get('/movements/{id}', [AccountController::class, 'getMovements']);
    });
    
    // Reports
    Route::prefix('reports')->group(function () {
        // Financial Reports
        Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
        Route::get('/summary', [ReportController::class, 'summary']);
        
        // Chart Data
        Route::get('/chart-data', [ReportController::class, 'chartData']);
        
        // Export endpoints
        Route::get('/export/excel', [ReportController::class, 'exportExcel']);
        Route::get('/export/pdf', [ReportController::class, 'exportPdf']);
        Route::get('/export/csv', [ReportController::class, 'exportCsv']);
        
        // Legacy reports (mantener compatibilidad)
        Route::get('/income-expense', [ReportController::class, 'incomeExpenseReport']);
        Route::get('/financial-concepts', [ReportController::class, 'financialConceptsReport']);
        Route::get('/monthly-summary', [ReportController::class, 'monthlySummary']);
    });
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'getSummary']);
        Route::get('/recent-transactions', [DashboardController::class, 'getRecentTransactions']);
        Route::get('/monthly-trends', [DashboardController::class, 'getMonthlyTrends']);
        Route::get('/top-concepts', [DashboardController::class, 'getTopConcepts']);
    });
});
