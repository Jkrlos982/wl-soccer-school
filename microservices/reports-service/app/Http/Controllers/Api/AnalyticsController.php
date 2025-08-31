<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get KPI data
     */
    public function kpis(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metrics' => 'required|array',
            'metrics.*' => 'string|in:total_users,active_users,revenue,conversion_rate,retention_rate,churn_rate,avg_session_duration,page_views,bounce_rate,customer_satisfaction',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'period' => 'nullable|string|in:day,week,month,quarter,year',
            'filters' => 'nullable|array',
            'compare_period' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
            $period = $request->get('period', 'day');
            $filters = $request->get('filters', []);
            $comparePeriod = $request->boolean('compare_period', false);

            $kpis = $this->analyticsService->getKPIs(
                $request->metrics,
                $dateFrom,
                $dateTo,
                $period,
                $filters,
                $comparePeriod
            );

            return response()->json([
                'success' => true,
                'data' => $kpis,
                'meta' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'period' => $period,
                    'compare_period' => $comparePeriod
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KPI data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trend analysis data
     */
    public function trends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'granularity' => 'nullable|string|in:hour,day,week,month',
            'filters' => 'nullable|array',
            'forecast_days' => 'nullable|integer|min:1|max:90'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
            $granularity = $request->get('granularity', 'day');
            $filters = $request->get('filters', []);
            $forecastDays = $request->get('forecast_days', 0);

            $trends = $this->analyticsService->getTrendAnalysis(
                $request->metric,
                $dateFrom,
                $dateTo,
                $granularity,
                $filters,
                $forecastDays
            );

            return response()->json([
                'success' => true,
                'data' => $trends,
                'meta' => [
                    'metric' => $request->metric,
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'granularity' => $granularity,
                    'forecast_days' => $forecastDays
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trend data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comparison analysis
     */
    public function comparison(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metrics' => 'required|array',
            'metrics.*' => 'string',
            'segments' => 'required|array',
            'segments.*' => 'array',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'period' => 'nullable|string|in:day,week,month,quarter,year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
            $period = $request->get('period', 'day');

            $comparison = $this->analyticsService->getComparisonAnalysis(
                $request->metrics,
                $request->segments,
                $dateFrom,
                $dateTo,
                $period
            );

            return response()->json([
                'success' => true,
                'data' => $comparison,
                'meta' => [
                    'metrics' => $request->metrics,
                    'segments_count' => count($request->segments),
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'period' => $period
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comparison data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribution analysis
     */
    public function distribution(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dimension' => 'required|string',
            'metric' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'filters' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:value,count,percentage',
            'sort_direction' => 'nullable|string|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
            $filters = $request->get('filters', []);
            $limit = $request->get('limit', 20);
            $sortBy = $request->get('sort_by', 'value');
            $sortDirection = $request->get('sort_direction', 'desc');

            $distribution = $this->analyticsService->getDistributionAnalysis(
                $request->dimension,
                $request->metric,
                $dateFrom,
                $dateTo,
                $filters,
                $limit,
                $sortBy,
                $sortDirection
            );

            return response()->json([
                'success' => true,
                'data' => $distribution,
                'meta' => [
                    'dimension' => $request->dimension,
                    'metric' => $request->metric,
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'limit' => $limit,
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch distribution data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time analytics data
     */
    public function realTime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metrics' => 'required|array',
            'metrics.*' => 'string',
            'refresh_interval' => 'nullable|integer|min:5|max:300'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $refreshInterval = $request->get('refresh_interval', 30);

            $realTimeData = $this->analyticsService->getRealTimeData(
                $request->metrics,
                $refreshInterval
            );

            return response()->json([
                'success' => true,
                'data' => $realTimeData,
                'meta' => [
                    'metrics' => $request->metrics,
                    'refresh_interval' => $refreshInterval,
                    'timestamp' => now()->toISOString(),
                    'next_refresh' => now()->addSeconds($refreshInterval)->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch real-time data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:kpis,trends,comparison,distribution',
            'format' => 'required|string|in:csv,excel,json,pdf',
            'data_params' => 'required|array',
            'filename' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filename = $request->get('filename', 'analytics_export_' . now()->format('Y-m-d_H-i-s'));

            $exportResult = $this->analyticsService->exportData(
                $request->type,
                $request->format,
                $request->data_params,
                $filename
            );

            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'data' => [
                    'download_url' => $exportResult['download_url'],
                    'filename' => $exportResult['filename'],
                    'file_size' => $exportResult['file_size'],
                    'expires_at' => $exportResult['expires_at']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cache_keys' => 'nullable|array',
            'cache_keys.*' => 'string',
            'clear_all' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cacheKeys = $request->get('cache_keys', []);
            $clearAll = $request->boolean('clear_all', false);

            $result = $this->analyticsService->clearCache($cacheKeys, $clearAll);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'data' => [
                    'cleared_keys' => $result['cleared_keys'],
                    'total_cleared' => $result['total_cleared']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics dashboard summary
     */
    public function dashboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
            $filters = $request->get('filters', []);

            $dashboard = $this->analyticsService->getDashboardSummary(
                $dateFrom,
                $dateTo,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $dashboard,
                'meta' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available metrics and dimensions
     */
    public function metadata(): JsonResponse
    {
        try {
            $metadata = $this->analyticsService->getMetadata();

            return response()->json([
                'success' => true,
                'data' => $metadata
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch metadata',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Legacy methods for backward compatibility

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->dashboard($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not supported. Use specific analytics endpoints.'
        ], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not supported. Use specific analytics endpoints.'
        ], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not supported. Use specific analytics endpoints.'
        ], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->clearCache(request());
    }
}
