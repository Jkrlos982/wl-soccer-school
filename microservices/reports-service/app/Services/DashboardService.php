<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\DashboardLayout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class DashboardService
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get widget data for reports
     */
    public function getWidgetData(array $source, array $parameters = [])
    {
        $widget = DashboardWidget::find($source['widget_id']);
        
        if (!$widget) {
            throw new Exception("Widget not found: {$source['widget_id']}");
        }

        return $this->fetchWidgetData($widget, $parameters);
    }

    /**
     * Fetch data for a specific widget
     */
    public function fetchWidgetData(DashboardWidget $widget, array $parameters = [])
    {
        $cacheKey = $this->generateWidgetCacheKey($widget, $parameters);
        $cacheDuration = $widget->cache_duration ?? 300;

        if ($widget->is_real_time) {
            return $this->fetchRealTimeWidgetData($widget, $parameters);
        }

        return Cache::remember($cacheKey, $cacheDuration, function () use ($widget, $parameters) {
            return $this->processWidgetData($widget, $parameters);
        });
    }

    /**
     * Process widget data based on configuration
     */
    protected function processWidgetData(DashboardWidget $widget, array $parameters)
    {
        $dataSource = $widget->data_source;
        $config = $widget->configuration;

        switch ($widget->type) {
            case 'chart':
                return $this->processChartData($widget, $dataSource, $config, $parameters);
            case 'metric':
                return $this->processMetricData($widget, $dataSource, $config, $parameters);
            case 'table':
                return $this->processTableData($widget, $dataSource, $config, $parameters);
            case 'list':
                return $this->processListData($widget, $dataSource, $config, $parameters);
            case 'gauge':
                return $this->processGaugeData($widget, $dataSource, $config, $parameters);
            default:
                throw new Exception("Unsupported widget type: {$widget->type}");
        }
    }

    /**
     * Process chart widget data
     */
    protected function processChartData(DashboardWidget $widget, array $dataSource, array $config, array $parameters)
    {
        $query = $this->buildWidgetQuery($dataSource, $parameters);
        
        switch ($widget->chart_type) {
            case 'line':
            case 'area':
                return $this->processTimeSeriesData($query, $config);
            case 'bar':
            case 'column':
                return $this->processCategoryData($query, $config);
            case 'pie':
            case 'doughnut':
                return $this->processDistributionData($query, $config);
            case 'scatter':
                return $this->processScatterData($query, $config);
            default:
                return $this->processCategoryData($query, $config);
        }
    }

    /**
     * Process metric widget data
     */
    protected function processMetricData(DashboardWidget $widget, array $dataSource, array $config, array $parameters)
    {
        $query = $this->buildWidgetQuery($dataSource, $parameters);
        
        $value = $this->calculateAggregation($query, $config['aggregation'] ?? 'count', $config['field'] ?? null);
        
        $result = [
            'value' => $value,
            'formatted_value' => $this->formatMetricValue($value, $config),
            'change' => null,
            'change_percentage' => null
        ];

        // Calculate change if comparison period is specified
        if (isset($config['compare_period'])) {
            $previousValue = $this->calculatePreviousPeriodValue($dataSource, $config, $parameters);
            $result['change'] = $value - $previousValue;
            $result['change_percentage'] = $this->analyticsService->calculateGrowth($value, $previousValue);
        }

        return $result;
    }

    /**
     * Process table widget data
     */
    protected function processTableData(DashboardWidget $widget, array $dataSource, array $config, array $parameters)
    {
        $query = $this->buildWidgetQuery($dataSource, $parameters);
        
        // Apply columns selection
        if (isset($config['columns'])) {
            $query->select($config['columns']);
        }
        
        // Apply sorting
        if (isset($config['sort'])) {
            foreach ($config['sort'] as $sort) {
                $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
            }
        }
        
        // Apply pagination
        $limit = $config['limit'] ?? 100;
        $offset = $parameters['offset'] ?? 0;
        
        return [
            'data' => $query->limit($limit)->offset($offset)->get()->toArray(),
            'total' => $query->count(),
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Process list widget data
     */
    protected function processListData(DashboardWidget $widget, array $dataSource, array $config, array $parameters)
    {
        $query = $this->buildWidgetQuery($dataSource, $parameters);
        
        // Apply grouping and aggregation for lists
        if (isset($config['group_by'])) {
            $query->select(
                $config['group_by'] . ' as label',
                DB::raw($this->getAggregationFunction($config) . ' as value')
            )
            ->groupBy($config['group_by'])
            ->orderBy('value', 'desc');
        }
        
        $limit = $config['limit'] ?? 10;
        
        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Process gauge widget data
     */
    protected function processGaugeData(DashboardWidget $widget, array $dataSource, array $config, array $parameters)
    {
        $query = $this->buildWidgetQuery($dataSource, $parameters);
        
        $value = $this->calculateAggregation($query, $config['aggregation'] ?? 'count', $config['field'] ?? null);
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 100;
        
        return [
            'value' => $value,
            'min' => $min,
            'max' => $max,
            'percentage' => min(100, max(0, (($value - $min) / ($max - $min)) * 100)),
            'formatted_value' => $this->formatMetricValue($value, $config)
        ];
    }

    /**
     * Build query for widget data
     */
    protected function buildWidgetQuery(array $dataSource, array $parameters)
    {
        $query = DB::table($dataSource['table']);
        
        // Apply base filters from data source
        if (isset($dataSource['filters'])) {
            foreach ($dataSource['filters'] as $filter) {
                $query->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }
        
        // Apply parameter filters
        if (isset($parameters['filters'])) {
            foreach ($parameters['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }
        
        // Apply date range if specified
        if (isset($parameters['date_from']) && isset($parameters['date_to'])) {
            $dateField = $dataSource['date_field'] ?? 'created_at';
            $query->whereBetween($dateField, [$parameters['date_from'], $parameters['date_to']]);
        }
        
        return $query;
    }

    /**
     * Process time series data for charts
     */
    protected function processTimeSeriesData($query, array $config)
    {
        $dateField = $config['date_field'] ?? 'created_at';
        $interval = $config['interval'] ?? 'day';
        
        $data = $query->select(
            DB::raw($this->getDateGrouping($interval, $dateField) . ' as period'),
            DB::raw($this->getAggregationFunction($config) . ' as value')
        )
        ->groupBy('period')
        ->orderBy('period')
        ->get()
        ->toArray();
        
        return [
            'labels' => array_column($data, 'period'),
            'values' => array_column($data, 'value')
        ];
    }

    /**
     * Process category data for charts
     */
    protected function processCategoryData($query, array $config)
    {
        $groupField = $config['group_field'];
        
        $data = $query->select(
            $groupField . ' as category',
            DB::raw($this->getAggregationFunction($config) . ' as value')
        )
        ->groupBy($groupField)
        ->orderBy('value', 'desc')
        ->get()
        ->toArray();
        
        return [
            'labels' => array_column($data, 'category'),
            'values' => array_column($data, 'value')
        ];
    }

    /**
     * Process distribution data for pie charts
     */
    protected function processDistributionData($query, array $config)
    {
        return $this->processCategoryData($query, $config);
    }

    /**
     * Process scatter plot data
     */
    protected function processScatterData($query, array $config)
    {
        $xField = $config['x_field'];
        $yField = $config['y_field'];
        
        $data = $query->select($xField . ' as x', $yField . ' as y')
            ->get()
            ->toArray();
        
        return [
            'data' => $data
        ];
    }

    /**
     * Fetch real-time widget data
     */
    protected function fetchRealTimeWidgetData(DashboardWidget $widget, array $parameters)
    {
        // For real-time widgets, always fetch fresh data
        return $this->processWidgetData($widget, $parameters);
    }

    /**
     * Calculate aggregation value
     */
    protected function calculateAggregation($query, $aggregation, $field = null)
    {
        switch ($aggregation) {
            case 'count':
                return $query->count();
            case 'sum':
                return $query->sum($field);
            case 'avg':
                return $query->avg($field);
            case 'max':
                return $query->max($field);
            case 'min':
                return $query->min($field);
            default:
                return $query->count();
        }
    }

    /**
     * Get aggregation function SQL
     */
    protected function getAggregationFunction(array $config)
    {
        $aggregation = $config['aggregation'] ?? 'count';
        $field = $config['field'] ?? '*';
        
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
     * Get date grouping SQL
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
     * Format metric value
     */
    protected function formatMetricValue($value, array $config)
    {
        $format = $config['format'] ?? 'number';
        
        switch ($format) {
            case 'currency':
                return '$' . number_format($value, 2);
            case 'percentage':
                return number_format($value, 2) . '%';
            case 'decimal':
                return number_format($value, $config['decimals'] ?? 2);
            case 'number':
            default:
                return number_format($value);
        }
    }

    /**
     * Calculate previous period value for comparison
     */
    protected function calculatePreviousPeriodValue(array $dataSource, array $config, array $parameters)
    {
        // Implementation for calculating previous period values
        // This would involve adjusting the date range and recalculating
        return 0;
    }

    /**
     * Generate cache key for widget
     */
    protected function generateWidgetCacheKey(DashboardWidget $widget, array $parameters)
    {
        return 'widget_' . $widget->id . '_' . md5(json_encode($parameters));
    }

    /**
     * Refresh widget cache
     */
    public function refreshWidgetCache(DashboardWidget $widget)
    {
        $widget->clearCache();
        return $this->fetchWidgetData($widget);
    }

    /**
     * Get dashboard layout with widgets
     */
    public function getDashboardLayout($layoutId, array $parameters = [])
    {
        $layout = DashboardLayout::with('widgets')->findOrFail($layoutId);
        
        $widgetsData = [];
        foreach ($layout->widgets as $widget) {
            $widgetsData[$widget->id] = $this->fetchWidgetData($widget, $parameters);
        }
        
        return [
            'layout' => $layout,
            'widgets_data' => $widgetsData
        ];
    }

    /**
     * Create new widget
     */
    public function createWidget(array $data)
    {
        return DashboardWidget::create($data);
    }

    /**
     * Update widget configuration
     */
    public function updateWidget(DashboardWidget $widget, array $data)
    {
        $widget->update($data);
        $widget->clearCache();
        return $widget;
    }

    /**
     * Delete widget
     */
    public function deleteWidget(DashboardWidget $widget)
    {
        $widget->clearCache();
        return $widget->delete();
    }

    /**
     * Duplicate widget
     */
    public function duplicateWidget(DashboardWidget $widget, array $overrides = [])
    {
        return $widget->duplicate($overrides);
    }

    /**
     * Get widget performance metrics
     */
    public function getWidgetPerformance(DashboardWidget $widget)
    {
        return [
            'cache_hit_rate' => $widget->getCacheHitRate(),
            'average_load_time' => $widget->getAverageLoadTime(),
            'last_refresh' => $widget->getLastRefresh(),
            'data_freshness' => $widget->getDataFreshness()
        ];
    }
}