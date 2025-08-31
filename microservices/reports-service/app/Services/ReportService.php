<?php

namespace App\Services;

use App\Models\ReportTemplate;
use App\Models\GeneratedReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class ReportService
{
    protected $analyticsService;
    protected $dashboardService;

    public function __construct(AnalyticsService $analyticsService, DashboardService $dashboardService)
    {
        $this->analyticsService = $analyticsService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Generate a report from a template
     */
    public function generateReport(ReportTemplate $template, array $parameters = [], $userId = null)
    {
        try {
            // Create generated report record
            $generatedReport = GeneratedReport::create([
                'template_id' => $template->id,
                'name' => $this->buildReportName($template, $parameters),
                'description' => $template->description,
                'status' => 'processing',
                'format' => $template->format,
                'parameters' => array_merge($template->parameters ?? [], $parameters),
                'generated_by' => $userId,
                'generated_at' => now(),
                'expires_at' => $this->calculateExpirationDate($template),
            ]);

            // Process the report generation
            $this->processReportGeneration($generatedReport, $template);

            return $generatedReport;
        } catch (Exception $e) {
            Log::error('Report generation failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($generatedReport)) {
                $generatedReport->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Process report generation based on template configuration
     */
    protected function processReportGeneration(GeneratedReport $generatedReport, ReportTemplate $template)
    {
        // Fetch data based on template data sources
        $data = $this->fetchReportData($template, $generatedReport->parameters);

        // Apply filters and transformations
        $processedData = $this->processReportData($data, $template, $generatedReport->parameters);

        // Generate report content based on format
        $content = $this->generateReportContent($processedData, $template, $generatedReport);

        // Save report file
        $filePath = $this->saveReportFile($content, $generatedReport, $template->format);

        // Update generated report with file information
        $generatedReport->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => basename($filePath),
            'file_size' => Storage::size($filePath),
            'file_hash' => hash_file('sha256', Storage::path($filePath)),
            'metadata' => [
                'rows_count' => count($processedData),
                'generation_time' => now()->diffInSeconds($generatedReport->created_at),
                'data_sources' => $template->data_sources,
            ]
        ]);
    }

    /**
     * Fetch data from configured data sources
     */
    protected function fetchReportData(ReportTemplate $template, array $parameters)
    {
        $data = [];
        $dataSources = $template->data_sources ?? [];

        foreach ($dataSources as $source) {
            switch ($source['type']) {
                case 'database':
                    $data[$source['name']] = $this->fetchDatabaseData($source, $parameters);
                    break;
                case 'api':
                    $data[$source['name']] = $this->fetchApiData($source, $parameters);
                    break;
                case 'analytics':
                    $data[$source['name']] = $this->analyticsService->getAnalyticsData($source, $parameters);
                    break;
                case 'dashboard':
                    $data[$source['name']] = $this->dashboardService->getWidgetData($source, $parameters);
                    break;
            }
        }

        return $data;
    }

    /**
     * Fetch data from database
     */
    protected function fetchDatabaseData(array $source, array $parameters)
    {
        // Implementation for database queries
        // This would include dynamic query building based on source configuration
        return [];
    }

    /**
     * Fetch data from external API
     */
    protected function fetchApiData(array $source, array $parameters)
    {
        // Implementation for API calls
        // This would include HTTP client calls to external services
        return [];
    }

    /**
     * Process and transform report data
     */
    protected function processReportData(array $data, ReportTemplate $template, array $parameters)
    {
        $processedData = $data;

        // Apply filters
        if (isset($parameters['filters'])) {
            $processedData = $this->applyFilters($processedData, $parameters['filters']);
        }

        // Apply sorting
        if (isset($parameters['sort'])) {
            $processedData = $this->applySorting($processedData, $parameters['sort']);
        }

        // Apply aggregations
        if (isset($template->parameters['aggregations'])) {
            $processedData = $this->applyAggregations($processedData, $template->parameters['aggregations']);
        }

        return $processedData;
    }

    /**
     * Generate report content based on format
     */
    protected function generateReportContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        switch ($template->format) {
            case 'pdf':
                return $this->generatePdfContent($data, $template, $generatedReport);
            case 'excel':
                return $this->generateExcelContent($data, $template, $generatedReport);
            case 'csv':
                return $this->generateCsvContent($data, $template, $generatedReport);
            case 'json':
                return $this->generateJsonContent($data, $template, $generatedReport);
            case 'html':
                return $this->generateHtmlContent($data, $template, $generatedReport);
            default:
                throw new Exception("Unsupported report format: {$template->format}");
        }
    }

    /**
     * Generate PDF content
     */
    protected function generatePdfContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        // Implementation for PDF generation using libraries like TCPDF or DomPDF
        return '';
    }

    /**
     * Generate Excel content
     */
    protected function generateExcelContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        // Implementation for Excel generation using PhpSpreadsheet
        return '';
    }

    /**
     * Generate CSV content
     */
    protected function generateCsvContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        $csv = '';
        
        // Add headers if configured
        if (isset($template->template_content['headers'])) {
            $csv .= implode(',', $template->template_content['headers']) . "\n";
        }

        // Add data rows
        foreach ($data as $row) {
            if (is_array($row)) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
        }

        return $csv;
    }

    /**
     * Generate JSON content
     */
    protected function generateJsonContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        return json_encode([
            'report' => [
                'id' => $generatedReport->id,
                'name' => $generatedReport->name,
                'generated_at' => $generatedReport->generated_at->toISOString(),
                'template' => $template->name,
            ],
            'data' => $data,
            'metadata' => $generatedReport->metadata ?? []
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Generate HTML content
     */
    protected function generateHtmlContent(array $data, ReportTemplate $template, GeneratedReport $generatedReport)
    {
        // Implementation for HTML generation using template engines
        return '';
    }

    /**
     * Save report file to storage
     */
    protected function saveReportFile(string $content, GeneratedReport $generatedReport, string $format)
    {
        $fileName = $this->generateFileName($generatedReport, $format);
        $filePath = "reports/{$generatedReport->id}/{$fileName}";

        Storage::put($filePath, $content);

        return $filePath;
    }

    /**
     * Generate unique file name
     */
    protected function generateFileName(GeneratedReport $generatedReport, string $format)
    {
        $slug = Str::slug($generatedReport->name);
        $timestamp = $generatedReport->generated_at->format('Y-m-d_H-i-s');
        
        return "{$slug}_{$timestamp}.{$format}";
    }

    /**
     * Build report name from template and parameters
     */
    protected function buildReportName(ReportTemplate $template, array $parameters)
    {
        $name = $template->name;
        
        // Add date range if specified
        if (isset($parameters['date_from']) && isset($parameters['date_to'])) {
            $name .= " ({$parameters['date_from']} to {$parameters['date_to']})";
        }
        
        // Add timestamp
        $name .= " - " . now()->format('Y-m-d H:i:s');
        
        return $name;
    }

    /**
     * Calculate expiration date based on template settings
     */
    protected function calculateExpirationDate(ReportTemplate $template)
    {
        $retentionDays = $template->parameters['retention_days'] ?? 30;
        return now()->addDays($retentionDays);
    }

    /**
     * Apply filters to data
     */
    protected function applyFilters(array $data, array $filters)
    {
        // Implementation for data filtering
        return $data;
    }

    /**
     * Apply sorting to data
     */
    protected function applySorting(array $data, array $sort)
    {
        // Implementation for data sorting
        return $data;
    }

    /**
     * Apply aggregations to data
     */
    protected function applyAggregations(array $data, array $aggregations)
    {
        // Implementation for data aggregations (sum, count, avg, etc.)
        return $data;
    }

    /**
     * Schedule automatic report generation
     */
    public function scheduleReport(ReportTemplate $template, array $parameters = [])
    {
        if (!$template->is_scheduled) {
            throw new Exception('Template is not configured for scheduling');
        }

        // Implementation for scheduling reports using Laravel's task scheduler
        // This would typically involve creating jobs and scheduling them
    }

    /**
     * Get report generation status
     */
    public function getReportStatus($reportId)
    {
        return GeneratedReport::findOrFail($reportId);
    }

    /**
     * Delete expired reports
     */
    public function cleanupExpiredReports()
    {
        $expiredReports = GeneratedReport::where('expires_at', '<', now())
            ->where('status', 'completed')
            ->get();

        foreach ($expiredReports as $report) {
            // Delete file from storage
            if ($report->file_path && Storage::exists($report->file_path)) {
                Storage::delete($report->file_path);
            }

            // Delete database record
            $report->delete();
        }

        return $expiredReports->count();
    }

    /**
     * Get report download URL
     */
    public function getDownloadUrl(GeneratedReport $report)
    {
        if ($report->status !== 'completed' || !$report->file_path) {
            throw new Exception('Report is not ready for download');
        }

        if ($report->isExpired()) {
            throw new Exception('Report has expired');
        }

        // Increment download count
        $report->incrementDownloadCount();

        return Storage::url($report->file_path);
    }

    /**
     * Preview report data without generating file
     */
    public function previewReportData(ReportTemplate $template, array $parameters = [], int $limit = 10)
    {
        // Merge template parameters with provided parameters
        $mergedParameters = array_merge(
            $template->getParametersWithDefaults(),
            $parameters
        );

        // Fetch all report data using existing method
        $allData = $this->fetchReportData($template, $mergedParameters);
        
        $previewData = [];
        
        // Apply limit to each data source
        foreach ($allData as $sourceName => $sourceData) {
            if (is_array($sourceData)) {
                $limitedData = array_slice($sourceData, 0, $limit);
                $previewData[$sourceName] = [
                    'data' => $limitedData,
                    'count' => count($sourceData),
                    'limited' => count($sourceData) > $limit,
                    'preview_count' => count($limitedData)
                ];
            } else {
                $previewData[$sourceName] = [
                    'data' => $sourceData,
                    'count' => 1,
                    'limited' => false,
                    'preview_count' => 1
                ];
            }
        }

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'format' => $template->format
            ],
            'parameters' => $mergedParameters,
            'data_sources' => $previewData,
            'preview_limit' => $limit,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Get report statistics
     */
    public function getReportStatistics(array $filters = [])
    {
        $query = GeneratedReport::query();
        
        // Apply filters
        if (isset($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }
        
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        }
        
        if (isset($filters['generated_by'])) {
            $query->where('generated_by', $filters['generated_by']);
        }
        
        return [
            'total_reports' => $query->count(),
            'completed_reports' => $query->completed()->count(),
            'failed_reports' => $query->failed()->count(),
            'pending_reports' => $query->pending()->count(),
            'processing_reports' => $query->processing()->count(),
            'by_format' => $query->groupBy('format')
                ->selectRaw('format, count(*) as count')
                ->pluck('count', 'format')
                ->toArray(),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'total_downloads' => $query->sum('download_count'),
            'average_file_size' => $query->whereNotNull('file_size')->avg('file_size'),
            'recent_reports' => $query->latest()->limit(5)->get(['id', 'template_id', 'status', 'created_at'])
        ];
    }
}