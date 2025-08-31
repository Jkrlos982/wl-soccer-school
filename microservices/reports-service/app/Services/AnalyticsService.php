<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class AnalyticsService
{
    /**
     * Get analytics data based on source configuration
     */
    public function getAnalyticsData(array $source, array $parameters = [])
    {
        $cacheKey = $this->generateCacheKey($source, $parameters);
        $cacheDuration = $source['cache_duration'] ?? 300; // 5 minutes default

        return Cache::remember($cacheKey, $cacheDuration, function () use ($source, $parameters) {
            return $this->fetchAnalyticsData($source, $parameters);
        });
    }

    /**
     * Fetch analytics data from various sources
     */
    protected function fetchAnalyticsData(array $source, array $parameters)
    {
        switch ($source['analytics_type']) {
            case 'kpi':
                return $this->calculateKPIs($source, $parameters);
            case 'trend':
                return $this->calculateTrends($source, $parameters);
            case 'comparison':
                return $this->calculateComparisons($source, $parameters);
            case 'distribution':
                return $this->calculateDistribution($source, $parameters);
            default:
                throw new Exception("Unsupported analytics type: {$source['analytics_type']}");
        }
    }

    /**
     * Calculate Key Performance Indicators
     */
    protected function calculateKPIs(array $source, array $parameters)
    {
        $kpis = [];
        $metrics = $source['metrics'] ?? [];

        foreach ($metrics as $metric) {
            $kpis[$metric['name']] = $this->calculateMetric($metric, $parameters);
        }

        return $kpis;
    }

    /**
     * Calculate individual metric
     */
    protected function calculateMetric(array $metric, array $parameters)
    {
        $query = $this->buildMetricQuery($metric, $parameters);
        
        switch ($metric['aggregation']) {
            case 'count':
                return $query->count();
            case 'sum':
                return $query->sum($metric['field']);
            case 'avg':
                return $query->avg($metric['field']);
            case 'max':
                return $query->max($metric['field']);
            case 'min':
                return $query->min($metric['field']);
            default:
                return $query->count();
        }
    }

    /**
     * Build query for metric calculation
     */
    protected function buildMetricQuery(array $metric, array $parameters)
    {
        $query = DB::table($metric['table']);

        // Apply date filters
        if (isset($parameters['date_from']) && isset($parameters['date_to'])) {
            $dateField = $metric['date_field'] ?? 'created_at';
            $query->whereBetween($dateField, [$parameters['date_from'], $parameters['date_to']]);
        }

        // Apply additional filters
        if (isset($metric['filters'])) {
            foreach ($metric['filters'] as $filter) {
                $query->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }

        // Apply parameter-based filters
        if (isset($parameters['filters'])) {
            foreach ($parameters['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Calculate trend data over time
     */
    protected function calculateTrends(array $source, array $parameters)
    {
        $trends = [];
        $interval = $parameters['interval'] ?? 'day';
        $dateField = $source['date_field'] ?? 'created_at';
        
        $query = DB::table($source['table'])
            ->select(
                DB::raw($this->getDateGrouping($interval, $dateField) . ' as period'),
                DB::raw($this->getAggregationFunction($source) . ' as value')
            )
            ->groupBy('period')
            ->orderBy('period');

        // Apply date range
        if (isset($parameters['date_from']) && isset($parameters['date_to'])) {
            $query->whereBetween($dateField, [$parameters['date_from'], $parameters['date_to']]);
        }

        return $query->get()->toArray();
    }

    /**
     * Calculate comparison data
     */
    protected function calculateComparisons(array $source, array $parameters)
    {
        $comparisons = [];
        $groupField = $source['group_field'];
        
        $query = DB::table($source['table'])
            ->select(
                $groupField . ' as category',
                DB::raw($this->getAggregationFunction($source) . ' as value')
            )
            ->groupBy($groupField)
            ->orderBy('value', 'desc');

        // Apply filters
        if (isset($parameters['filters'])) {
            foreach ($parameters['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        // Apply limit
        if (isset($parameters['limit'])) {
            $query->limit($parameters['limit']);
        }

        return $query->get()->toArray();
    }

    /**
     * Calculate distribution data
     */
    protected function calculateDistribution(array $source, array $parameters)
    {
        $field = $source['distribution_field'];
        $ranges = $source['ranges'] ?? [];
        
        $distribution = [];
        
        foreach ($ranges as $range) {
            $query = DB::table($source['table'])
                ->whereBetween($field, [$range['min'], $range['max']]);
            
            // Apply additional filters
            if (isset($parameters['filters'])) {
                foreach ($parameters['filters'] as $filterField => $value) {
                    $query->where($filterField, $value);
                }
            }
            
            $distribution[] = [
                'range' => $range['label'],
                'count' => $query->count(),
                'min' => $range['min'],
                'max' => $range['max']
            ];
        }
        
        return $distribution;
    }

    /**
     * Get date grouping SQL based on interval
     */
    protected function getDateGrouping($interval, $dateField)
    {
        switch ($interval) {
            case 'hour':
                return "DATE_FORMAT({$dateField}, '%Y-%m-%d %H:00:00')";
            case 'day':
                return "DATE({$dateField})";
            case 'week':
                return "YEARWEEK({$dateField})";
            case 'month':
                return "DATE_FORMAT({$dateField}, '%Y-%m')";
            case 'year':
                return "YEAR({$dateField})";
            default:
                return "DATE({$dateField})";
        }
    }

    /**
     * Get aggregation function SQL
     */
    protected function getAggregationFunction(array $source)
    {
        $aggregation = $source['aggregation'] ?? 'count';
        $field = $source['field'] ?? '*';
        
        switch ($aggregation) {
            case 'count':
                return 'COUNT(*)';
            case 'sum':
                return "SUM({$field})";
            case 'avg':
                return "AVG({$field})";
            case 'max':
                return "MAX({$field})";
            case 'min':
                return "MIN({$field})";
            default:
                return 'COUNT(*)';
        }
    }

    /**
     * Calculate KPI value for a specific metric
     */
    private function calculateKPI(string $metric, $dateFrom, $dateTo, array $filters = []): float
    {
        $cacheKey = $this->generateCacheKey("kpi:{$metric}:" . md5(serialize([$dateFrom, $dateTo, $filters])));
        
        return Cache::remember($cacheKey, 3600, function () use ($metric, $dateFrom, $dateTo, $filters) {
            return match ($metric) {
                'total_users' => $this->fetchDatabaseValue('SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]),
                'active_users' => $this->fetchDatabaseValue('SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]),
                'revenue' => $this->fetchDatabaseValue('SELECT SUM(amount) FROM orders WHERE created_at BETWEEN ? AND ? AND status = "completed"', [$dateFrom, $dateTo]),
                'conversion_rate' => $this->calculateConversionRate($dateFrom, $dateTo, $filters),
                'retention_rate' => $this->calculateRetentionRate($dateFrom, $dateTo, $filters),
                'churn_rate' => $this->calculateChurnRate($dateFrom, $dateTo, $filters),
                'avg_session_duration' => $this->fetchDatabaseValue('SELECT AVG(duration) FROM user_sessions WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]),
                'page_views' => $this->fetchDatabaseValue('SELECT COUNT(*) FROM page_views WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]),
                'bounce_rate' => $this->calculateBounceRate($dateFrom, $dateTo, $filters),
                'customer_satisfaction' => $this->fetchDatabaseValue('SELECT AVG(rating) FROM customer_feedback WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]),
                default => 0.0
            };
        });
    }

    /**
     * Get trend data for a specific metric
     */
    private function getTrendData(string $metric, $dateFrom, $dateTo, string $granularity = 'day', array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey("trend:{$metric}:{$granularity}:" . md5(serialize([$dateFrom, $dateTo, $filters])));
        
        return Cache::remember($cacheKey, 1800, function () use ($metric, $dateFrom, $dateTo, $granularity, $filters) {
            $data = [];
            $current = $dateFrom->copy();
            
            while ($current->lte($dateTo)) {
                $next = $current->copy();
                match ($granularity) {
                    'hour' => $next->addHour(),
                    'day' => $next->addDay(),
                    'week' => $next->addWeek(),
                    'month' => $next->addMonth(),
                    default => $next->addDay()
                };
                
                $value = $this->calculateKPI($metric, $current, $next->subSecond(), $filters);
                
                $data[] = [
                    'date' => $current->toDateString(),
                    'value' => $value,
                    'period' => $granularity
                ];
                
                $current = $next->addSecond();
            }
            
            return $data;
        });
    }

    /**
     * Get distribution data for a dimension and metric
     */
    private function getDistributionData(string $dimension, string $metric, $dateFrom, $dateTo, array $filters = [], int $limit = 20): array
    {
        $cacheKey = $this->generateCacheKey("distribution:{$dimension}:{$metric}:" . md5(serialize([$dateFrom, $dateTo, $filters, $limit])));
        
        return Cache::remember($cacheKey, 1800, function () use ($dimension, $metric, $dateFrom, $dateTo, $filters, $limit) {
            // Mock data - in real implementation, this would query the database
            $data = [];
            
            switch ($dimension) {
                case 'user_segment':
                    $segments = ['Premium', 'Standard', 'Basic', 'Trial'];
                    foreach ($segments as $segment) {
                        $segmentFilters = array_merge($filters, ['user_segment' => $segment]);
                        $value = $this->calculateKPI($metric, $dateFrom, $dateTo, $segmentFilters);
                        $data[] = [
                            'dimension_value' => $segment,
                            'value' => $value,
                            'count' => rand(100, 1000)
                        ];
                    }
                    break;
                    
                case 'device_type':
                    $devices = ['Desktop', 'Mobile', 'Tablet'];
                    foreach ($devices as $device) {
                        $deviceFilters = array_merge($filters, ['device_type' => $device]);
                        $value = $this->calculateKPI($metric, $dateFrom, $dateTo, $deviceFilters);
                        $data[] = [
                            'dimension_value' => $device,
                            'value' => $value,
                            'count' => rand(50, 500)
                        ];
                    }
                    break;
                    
                default:
                    // Generic dimension handling
                    for ($i = 1; $i <= $limit; $i++) {
                        $data[] = [
                            'dimension_value' => "Value {$i}",
                            'value' => rand(10, 1000),
                            'count' => rand(1, 100)
                        ];
                    }
            }
            
            return $data;
        });
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($dateFrom, $dateTo, array $filters = []): float
    {
        $totalVisitors = $this->fetchDatabaseValue('SELECT COUNT(DISTINCT user_id) FROM page_views WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]);
        $conversions = $this->fetchDatabaseValue('SELECT COUNT(DISTINCT user_id) FROM orders WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]);
        
        return $totalVisitors > 0 ? ($conversions / $totalVisitors) * 100 : 0;
    }

    /**
     * Calculate retention rate
     */
    private function calculateRetentionRate($dateFrom, $dateTo, array $filters = []): float
    {
        // Mock calculation - in real implementation, this would be more complex
        return rand(70, 95) + (rand(0, 99) / 100);
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate($dateFrom, $dateTo, array $filters = []): float
    {
        // Mock calculation - in real implementation, this would be more complex
        return rand(5, 15) + (rand(0, 99) / 100);
    }

    /**
     * Calculate bounce rate
     */
    private function calculateBounceRate($dateFrom, $dateTo, array $filters = []): float
    {
        $totalSessions = $this->fetchDatabaseValue('SELECT COUNT(*) FROM user_sessions WHERE created_at BETWEEN ? AND ?', [$dateFrom, $dateTo]);
        $bounceSessions = $this->fetchDatabaseValue('SELECT COUNT(*) FROM user_sessions WHERE created_at BETWEEN ? AND ? AND page_views = 1', [$dateFrom, $dateTo]);
        
        return $totalSessions > 0 ? ($bounceSessions / $totalSessions) * 100 : 0;
    }

    /**
     * Fetch database value with prepared statement
     */
    private function fetchDatabaseValue(string $query, array $params = []): float
    {
        $result = DB::select($query, $params);
        return (float) ($result[0]->{'COUNT(*)'} ?? $result[0]->{'SUM(amount)'} ?? $result[0]->{'AVG(duration)'} ?? $result[0]->{'AVG(rating)'} ?? 0);
    }

    /**
     * Generate cache key for analytics data
     */
    protected function generateCacheKey($source, $parameters = null)
    {
        if (is_array($source) && $parameters !== null) {
            // Handle array source and parameters
            $key = 'analytics_' . md5(json_encode($source) . json_encode($parameters));
            return $key;
        } elseif (is_string($source)) {
            // Handle string key
            return "analytics:{$source}";
        }
        
        return 'analytics_default';
    }

    /**
     * Calculate period-over-period growth
     */
    public function calculateGrowth($currentValue, $previousValue)
    {
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        
        return (($currentValue - $previousValue) / $previousValue) * 100;
    }

    /**
     * Get real-time analytics data
     */
    public function getRealTimeData(array $metrics, int $minutes = 60)
    {
        if (is_array($metrics) && isset($metrics[0]) && is_string($metrics[0])) {
            // Handle array of metric names with minutes parameter
            $data = [];
            $dateFrom = Carbon::now()->subMinutes($minutes);
            $dateTo = Carbon::now();
            
            foreach ($metrics as $metric) {
                $data[$metric] = $this->calculateKPI($metric, $dateFrom, $dateTo);
            }
            
            return $data;
        } else {
            // Handle array of metric configurations
            $data = [];
            
            foreach ($metrics as $metric) {
                $data[$metric['name']] = $this->calculateMetric($metric, []);
            }
            
            return $data;
        }
    }

    /**
     * Get KPI data with comparison
     */
    public function getKPIs(array $metrics, $dateFrom, $dateTo, string $period = 'day', array $filters = [], bool $comparePeriod = false): array
    {
        $kpis = [];
        
        foreach ($metrics as $metric) {
            $value = $this->calculateKPI($metric, $dateFrom, $dateTo, $filters);
            $kpi = [
                'metric' => $metric,
                'value' => $value,
                'period' => $period,
                'date_range' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString()
                ]
            ];
            
            if ($comparePeriod) {
                $previousPeriod = $this->calculatePreviousPeriod($dateFrom, $dateTo);
                $previousValue = $this->calculateKPI($metric, $previousPeriod['from'], $previousPeriod['to'], $filters);
                $kpi['comparison'] = [
                    'previous_value' => $previousValue,
                    'change' => $value - $previousValue,
                    'change_percentage' => $previousValue > 0 ? (($value - $previousValue) / $previousValue) * 100 : 0
                ];
            }
            
            $kpis[] = $kpi;
        }
        
        return $kpis;
    }

    /**
     * Get trend analysis data
     */
    public function getTrendAnalysis(string $metric, $dateFrom, $dateTo, string $granularity = 'day', array $filters = [], int $forecastDays = 0): array
    {
        $data = $this->getTrendData($metric, $dateFrom, $dateTo, $granularity, $filters);
        
        $result = [
            'metric' => $metric,
            'granularity' => $granularity,
            'data' => $data,
            'statistics' => [
                'min' => min(array_column($data, 'value')),
                'max' => max(array_column($data, 'value')),
                'avg' => array_sum(array_column($data, 'value')) / count($data),
                'trend' => $this->calculateTrend($data)
            ]
        ];
        
        if ($forecastDays > 0) {
            $result['forecast'] = $this->generateForecast($data, $forecastDays, $granularity);
        }
        
        return $result;
    }

    /**
     * Get comparison analysis between segments
     */
    public function getComparisonAnalysis(array $metrics, array $segments, $dateFrom, $dateTo, string $period = 'day'): array
    {
        $comparisons = [];
        
        foreach ($metrics as $metric) {
            $metricComparison = [
                'metric' => $metric,
                'segments' => []
            ];
            
            foreach ($segments as $segmentName => $segmentFilters) {
                $value = $this->calculateKPI($metric, $dateFrom, $dateTo, $segmentFilters);
                $metricComparison['segments'][] = [
                    'name' => $segmentName,
                    'value' => $value,
                    'filters' => $segmentFilters
                ];
            }
            
            // Calculate segment performance
            $values = array_column($metricComparison['segments'], 'value');
            $metricComparison['statistics'] = [
                'best_performing' => $metricComparison['segments'][array_search(max($values), $values)],
                'worst_performing' => $metricComparison['segments'][array_search(min($values), $values)],
                'average' => array_sum($values) / count($values)
            ];
            
            $comparisons[] = $metricComparison;
        }
        
        return $comparisons;
    }

    /**
     * Get distribution analysis
     */
    public function getDistributionAnalysis(string $dimension, string $metric, $dateFrom, $dateTo, array $filters = [], int $limit = 20, string $sortBy = 'value', string $sortDirection = 'desc'): array
    {
        $data = $this->getDistributionData($dimension, $metric, $dateFrom, $dateTo, $filters, $limit);
        
        // Sort data
        usort($data, function($a, $b) use ($sortBy, $sortDirection) {
            $comparison = $a[$sortBy] <=> $b[$sortBy];
            return $sortDirection === 'desc' ? -$comparison : $comparison;
        });
        
        // Calculate percentages
        $total = array_sum(array_column($data, 'value'));
        foreach ($data as &$item) {
            $item['percentage'] = $total > 0 ? ($item['value'] / $total) * 100 : 0;
        }
        
        return [
            'dimension' => $dimension,
            'metric' => $metric,
            'data' => array_slice($data, 0, $limit),
            'total_value' => $total,
            'total_items' => count($data)
        ];
    }

    /**
     * Get dashboard summary with key metrics
     */
    public function getDashboardSummary($dateFrom, $dateTo, array $filters = []): array
    {
        $keyMetrics = ['total_users', 'active_users', 'revenue', 'conversion_rate'];
        $kpis = $this->getKPIs($keyMetrics, $dateFrom, $dateTo, 'day', $filters, true);
        
        return [
            'kpis' => $kpis,
            'trends' => [
                'users' => $this->getTrendAnalysis('active_users', $dateFrom, $dateTo, 'day', $filters),
                'revenue' => $this->getTrendAnalysis('revenue', $dateFrom, $dateTo, 'day', $filters)
            ],
            'top_segments' => $this->getDistributionAnalysis('user_segment', 'active_users', $dateFrom, $dateTo, $filters, 5),
            'real_time' => $this->getRealTimeData(['active_users', 'page_views'], 30)
        ];
    }

    /**
     * Get available metrics and dimensions metadata
     */
    public function getMetadata(): array
    {
        return [
            'metrics' => [
                'total_users' => ['name' => 'Total Users', 'type' => 'count', 'description' => 'Total number of users'],
                'active_users' => ['name' => 'Active Users', 'type' => 'count', 'description' => 'Number of active users'],
                'revenue' => ['name' => 'Revenue', 'type' => 'currency', 'description' => 'Total revenue generated'],
                'conversion_rate' => ['name' => 'Conversion Rate', 'type' => 'percentage', 'description' => 'Percentage of users who converted'],
                'retention_rate' => ['name' => 'Retention Rate', 'type' => 'percentage', 'description' => 'User retention rate'],
                'churn_rate' => ['name' => 'Churn Rate', 'type' => 'percentage', 'description' => 'User churn rate'],
                'avg_session_duration' => ['name' => 'Avg Session Duration', 'type' => 'duration', 'description' => 'Average session duration'],
                'page_views' => ['name' => 'Page Views', 'type' => 'count', 'description' => 'Total page views'],
                'bounce_rate' => ['name' => 'Bounce Rate', 'type' => 'percentage', 'description' => 'Percentage of single-page sessions'],
                'customer_satisfaction' => ['name' => 'Customer Satisfaction', 'type' => 'score', 'description' => 'Customer satisfaction score']
            ],
            'dimensions' => [
                'user_segment' => ['name' => 'User Segment', 'type' => 'categorical'],
                'device_type' => ['name' => 'Device Type', 'type' => 'categorical'],
                'traffic_source' => ['name' => 'Traffic Source', 'type' => 'categorical'],
                'geographic_region' => ['name' => 'Geographic Region', 'type' => 'categorical'],
                'user_type' => ['name' => 'User Type', 'type' => 'categorical']
            ],
            'periods' => ['hour', 'day', 'week', 'month', 'quarter', 'year'],
            'granularities' => ['hour', 'day', 'week', 'month']
        ];
    }

    /**
     * Calculate previous period for comparison
     */
    private function calculatePreviousPeriod($dateFrom, $dateTo): array
    {
        $daysDiff = $dateFrom->diffInDays($dateTo);
        
        return [
            'from' => $dateFrom->copy()->subDays($daysDiff + 1),
            'to' => $dateFrom->copy()->subDay()
        ];
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) return 'stable';
        
        $firstValue = $data[0]['value'];
        $lastValue = end($data)['value'];
        
        if ($lastValue > $firstValue * 1.05) return 'increasing';
        if ($lastValue < $firstValue * 0.95) return 'decreasing';
        
        return 'stable';
    }

    /**
     * Generate forecast data
     */
    private function generateForecast(array $historicalData, int $forecastDays, string $granularity): array
    {
        // Simple linear regression forecast
        $values = array_column($historicalData, 'value');
        $n = count($values);
        
        if ($n < 2) return [];
        
        // Calculate trend
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Generate forecast
        $forecast = [];
        $lastDate = Carbon::parse(end($historicalData)['date']);
        
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastValue = $intercept + $slope * ($n + $i);
            $forecastDate = $lastDate->copy()->addDays($i);
            
            $forecast[] = [
                'date' => $forecastDate->toDateString(),
                'value' => max(0, $forecastValue), // Ensure non-negative
                'confidence' => max(0.1, 1 - ($i / $forecastDays) * 0.5) // Decreasing confidence
            ];
        }
        
        return $forecast;
    }

    /**
     * Clear analytics cache
     */
    public function clearCache($pattern = 'analytics_*')
    {
        // Implementation depends on cache driver
        // For Redis: Cache::getRedis()->flushdb();
        // For file cache: need to iterate and delete matching keys
    }

    /**
     * Export analytics data
     */
    public function exportData(array $source, array $parameters, $format = 'json')
    {
        $data = $this->getAnalyticsData($source, $parameters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data);
            case 'excel':
                return $this->exportToExcel($data);
            case 'json':
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Export data to CSV format
     */
    protected function exportToCsv(array $data)
    {
        // Implementation for CSV export
        return '';
    }

    /**
     * Export data to Excel format
     */
    protected function exportToExcel(array $data)
    {
        // Implementation for Excel export
        return '';
    }
}