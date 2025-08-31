<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use App\Models\GeneratedReport;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all report templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = ReportTemplate::with(['generatedReports' => function($q) {
            $q->latest()->limit(5);
        }]);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $templates = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Create a new report template
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:financial,operational,analytics,custom',
            'data_sources' => 'required|array',
            'parameters' => 'nullable|array',
            'format' => 'required|in:pdf,excel,csv,json,html',
            'template_config' => 'required|array',
            'schedule_config' => 'nullable|array',
            'is_public' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = ReportTemplate::create(array_merge(
            $validator->validated(),
            ['created_by' => Auth::id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Report template created successfully',
            'data' => $template->load('generatedReports')
        ], 201);
    }

    /**
     * Get a specific report template
     */
    public function showTemplate(ReportTemplate $template): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $template->load(['generatedReports' => function($q) {
                $q->latest()->limit(10);
            }])
        ]);
    }

    /**
     * Update a report template
     */
    public function updateTemplate(Request $request, ReportTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:financial,operational,analytics,custom',
            'data_sources' => 'sometimes|array',
            'parameters' => 'nullable|array',
            'format' => 'sometimes|in:pdf,excel,csv,json,html',
            'template_config' => 'sometimes|array',
            'schedule_config' => 'nullable|array',
            'is_public' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template->update(array_merge(
            $validator->validated(),
            ['updated_by' => Auth::id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Report template updated successfully',
            'data' => $template->fresh()->load('generatedReports')
        ]);
    }

    /**
     * Delete a report template
     */
    public function destroyTemplate(ReportTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report template deleted successfully'
        ]);
    }

    /**
     * Generate a report from template
     */
    public function generate(Request $request, ReportTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parameters' => 'nullable|array',
            'format' => 'nullable|in:pdf,excel,csv,json,html',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date',
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
            $report = $this->reportService->generateReport(
                $template,
                $request->get('parameters', []),
                $request->get('format', $template->format),
                Auth::id(),
                $request->only(['date_range', 'filters'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Report generation started',
                'data' => $report
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all generated reports
     */
    public function reports(Request $request): JsonResponse
    {
        $query = GeneratedReport::with(['template', 'generatedBy']);

        // Apply filters
        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('format')) {
            $query->where('format', $request->format);
        }

        if ($request->has('generated_by')) {
            $query->where('generated_by', $request->generated_by);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Get a specific generated report
     */
    public function showReport(GeneratedReport $report): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $report->load(['template', 'generatedBy'])
        ]);
    }

    /**
     * Download a generated report
     */
    public function download(GeneratedReport $report)
    {
        if ($report->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Report is not ready for download'
            ], 400);
        }

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Report file not found'
            ], 404);
        }

        // Increment download count
        $report->incrementDownloadCount();

        return Storage::download($report->file_path, $report->filename);
    }

    /**
     * Get report generation status
     */
    public function status(GeneratedReport $report): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'progress' => $report->progress,
                'error_message' => $report->error_message,
                'file_size' => $report->getFormattedFileSize(),
                'download_count' => $report->download_count,
                'expires_at' => $report->expires_at,
                'is_expired' => $report->isExpired(),
                'download_url' => $report->getDownloadUrl()
            ]
        ]);
    }

    /**
     * Cancel report generation
     */
    public function cancel(GeneratedReport $report): JsonResponse
    {
        if (!in_array($report->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel report in current status'
            ], 400);
        }

        $report->update([
            'status' => 'cancelled',
            'error_message' => 'Cancelled by user'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report generation cancelled'
        ]);
    }

    /**
     * Delete a generated report
     */
    public function destroyReport(GeneratedReport $report): JsonResponse
    {
        // Delete associated files
        $report->deleteFiles();
        
        // Delete the record
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully'
        ]);
    }

    /**
     * Get report statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->reportService->getReportStatistics($request->all());

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicate a report template
     */
    public function duplicateTemplate(ReportTemplate $template): JsonResponse
    {
        $duplicate = $template->replicate();
        $duplicate->name = $template->name . ' (Copy)';
        $duplicate->created_by = Auth::id();
        $duplicate->updated_by = Auth::id();
        $duplicate->save();

        return response()->json([
            'success' => true,
            'message' => 'Report template duplicated successfully',
            'data' => $duplicate
        ], 201);
    }

    /**
     * Preview report data without generating file
     */
    public function preview(Request $request, ReportTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parameters' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preview = $this->reportService->previewReportData(
                $template,
                $request->get('parameters', []),
                $request->get('limit', 10)
            );

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
