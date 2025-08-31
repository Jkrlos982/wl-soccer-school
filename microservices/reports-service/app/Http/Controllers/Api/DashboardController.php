<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DashboardLayout;
use App\Models\DashboardWidget;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    // Dashboard Layout Management
    
    /**
     * Get all dashboard layouts
     */
    public function layouts(Request $request): JsonResponse
    {
        $query = DashboardLayout::with(['createdBy', 'updatedBy', 'widgets'])
            ->when($request->user_id, function ($q, $userId) {
                return $q->forUser($userId);
            })
            ->when($request->is_public !== null, function ($q) use ($request) {
                return $request->is_public ? $q->public() : $q->where('is_public', false);
            })
            ->when($request->is_default !== null, function ($q) use ($request) {
                return $request->is_default ? $q->default() : $q->where('is_default', false);
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('name');

        $layouts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $layouts
        ]);
    }

    /**
     * Create a new dashboard layout
     */
    public function createLayout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'theme_config' => 'nullable|array',
            'grid_config' => 'nullable|array',
            'widgets' => 'nullable|array',
            'widgets.*.widget_id' => 'required_with:widgets|exists:dashboard_widgets,id',
            'widgets.*.position' => 'required_with:widgets|array',
            'widgets.*.size' => 'required_with:widgets|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout = DashboardLayout::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_public' => $request->get('is_public', false),
            'is_default' => $request->get('is_default', false),
            'theme_config' => $request->theme_config ?? DashboardLayout::getDefaultThemeConfig(),
            'grid_config' => $request->grid_config ?? DashboardLayout::getDefaultGridConfig(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        // Add widgets to layout
        if ($request->widgets) {
            foreach ($request->widgets as $widgetData) {
                $layout->addWidget(
                    $widgetData['widget_id'],
                    $widgetData['position'],
                    $widgetData['size']
                );
            }
        }

        $layout->load(['createdBy', 'updatedBy', 'widgets']);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout created successfully',
            'data' => $layout
        ], 201);
    }

    /**
     * Get a specific dashboard layout
     */
    public function showLayout(DashboardLayout $layout): JsonResponse
    {
        $layout->load(['createdBy', 'updatedBy', 'widgets.widget']);

        return response()->json([
            'success' => true,
            'data' => $layout
        ]);
    }

    /**
     * Update a dashboard layout
     */
    public function updateLayout(Request $request, DashboardLayout $layout): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'theme_config' => 'nullable|array',
            'grid_config' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout->update(array_merge(
            $request->only(['name', 'description', 'is_public', 'is_default', 'theme_config', 'grid_config']),
            ['updated_by' => Auth::id()]
        ));

        $layout->load(['createdBy', 'updatedBy', 'widgets']);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout updated successfully',
            'data' => $layout
        ]);
    }

    /**
     * Delete a dashboard layout
     */
    public function destroyLayout(DashboardLayout $layout): JsonResponse
    {
        if ($layout->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default layout'
            ], 400);
        }

        $layout->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout deleted successfully'
        ]);
    }

    /**
     * Duplicate a dashboard layout
     */
    public function duplicateLayout(DashboardLayout $layout, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $duplicated = $layout->duplicate($request->name);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout duplicated successfully',
            'data' => $duplicated
        ], 201);
    }

    /**
     * Set layout as default
     */
    public function setDefaultLayout(DashboardLayout $layout): JsonResponse
    {
        $layout->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Layout set as default successfully',
            'data' => $layout
        ]);
    }

    // Widget Management

    /**
     * Get all dashboard widgets
     */
    public function widgets(Request $request): JsonResponse
    {
        $query = DashboardWidget::with(['createdBy', 'updatedBy'])
            ->when($request->type, function ($q, $type) {
                return $q->byType($type);
            })
            ->when($request->chart_type, function ($q, $chartType) {
                return $q->byChartType($chartType);
            })
            ->when($request->is_active !== null, function ($q) use ($request) {
                return $request->is_active ? $q->active() : $q->where('is_active', false);
            })
            ->when($request->is_public !== null, function ($q) use ($request) {
                return $request->is_public ? $q->public() : $q->where('is_public', false);
            })
            ->when($request->is_real_time !== null, function ($q) use ($request) {
                return $request->is_real_time ? $q->realTime() : $q->where('is_real_time', false);
            })
            ->ordered();

        $widgets = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $widgets
        ]);
    }

    /**
     * Create a new dashboard widget
     */
    public function createWidget(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['chart', 'metric', 'table', 'list', 'gauge'])],
            'chart_type' => 'nullable|string|in:line,bar,pie,doughnut,area,scatter',
            'description' => 'nullable|string',
            'data_source' => 'required|array',
            'configuration' => 'nullable|array',
            'refresh_interval' => 'nullable|integer|min:5',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_real_time' => 'boolean',
            'cache_duration' => 'nullable|integer|min:0',
            'order_index' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $widget = DashboardWidget::create([
            'name' => $request->name,
            'type' => $request->type,
            'chart_type' => $request->chart_type,
            'description' => $request->description,
            'data_source' => $request->data_source,
            'configuration' => $request->configuration ?? DashboardWidget::getDefaultConfiguration($request->type),
            'refresh_interval' => $request->get('refresh_interval', 300),
            'is_active' => $request->get('is_active', true),
            'is_public' => $request->get('is_public', false),
            'is_real_time' => $request->get('is_real_time', false),
            'cache_duration' => $request->get('cache_duration', 300),
            'order_index' => $request->order_index ?? 0,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        $widget->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard widget created successfully',
            'data' => $widget
        ], 201);
    }

    /**
     * Get a specific dashboard widget
     */
    public function showWidget(DashboardWidget $widget): JsonResponse
    {
        $widget->load(['createdBy', 'updatedBy', 'layouts']);

        return response()->json([
            'success' => true,
            'data' => $widget
        ]);
    }

    /**
     * Update a dashboard widget
     */
    public function updateWidget(Request $request, DashboardWidget $widget): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['chart', 'metric', 'table', 'list', 'gauge'])],
            'chart_type' => 'nullable|string|in:line,bar,pie,doughnut,area,scatter',
            'description' => 'nullable|string',
            'data_source' => 'sometimes|array',
            'configuration' => 'nullable|array',
            'refresh_interval' => 'nullable|integer|min:5',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_real_time' => 'boolean',
            'cache_duration' => 'nullable|integer|min:0',
            'order_index' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $widget->update(array_merge(
            $request->only([
                'name', 'type', 'chart_type', 'description', 'data_source',
                'configuration', 'refresh_interval', 'is_active', 'is_public',
                'is_real_time', 'cache_duration', 'order_index'
            ]),
            ['updated_by' => Auth::id()]
        ));

        // Clear widget cache when updated
        $widget->clearCache();

        $widget->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard widget updated successfully',
            'data' => $widget
        ]);
    }

    /**
     * Delete a dashboard widget
     */
    public function destroyWidget(DashboardWidget $widget): JsonResponse
    {
        $widget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard widget deleted successfully'
        ]);
    }

    /**
     * Duplicate a dashboard widget
     */
    public function duplicateWidget(DashboardWidget $widget, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $duplicated = $widget->duplicate($request->name);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard widget duplicated successfully',
            'data' => $duplicated
        ], 201);
    }

    /**
     * Get widget data
     */
    public function getWidgetData(DashboardWidget $widget, Request $request): JsonResponse
    {
        $parameters = $request->get('parameters', []);
        $forceRefresh = $request->boolean('force_refresh', false);

        try {
            $data = $this->dashboardService->fetchWidgetData($widget, $parameters, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $data,
                'widget' => [
                    'id' => $widget->id,
                    'name' => $widget->name,
                    'type' => $widget->type,
                    'chart_type' => $widget->chart_type,
                    'last_updated' => $widget->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch widget data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh widget data
     */
    public function refreshWidget(DashboardWidget $widget): JsonResponse
    {
        try {
            $widget->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Widget refreshed successfully',
                'data' => $widget
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh widget',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Layout Widget Management

    /**
     * Add widget to layout
     */
    public function addWidgetToLayout(DashboardLayout $layout, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'widget_id' => 'required|exists:dashboard_widgets,id',
            'position' => 'required|array',
            'position.x' => 'required|integer|min:0',
            'position.y' => 'required|integer|min:0',
            'size' => 'required|array',
            'size.width' => 'required|integer|min:1',
            'size.height' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout->addWidget(
            $request->widget_id,
            $request->position,
            $request->size
        );

        return response()->json([
            'success' => true,
            'message' => 'Widget added to layout successfully'
        ]);
    }

    /**
     * Update widget in layout
     */
    public function updateWidgetInLayout(DashboardLayout $layout, DashboardWidget $widget, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'position' => 'sometimes|array',
            'position.x' => 'required_with:position|integer|min:0',
            'position.y' => 'required_with:position|integer|min:0',
            'size' => 'sometimes|array',
            'size.width' => 'required_with:size|integer|min:1',
            'size.height' => 'required_with:size|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout->updateWidget(
            $widget->id,
            $request->position,
            $request->size
        );

        return response()->json([
            'success' => true,
            'message' => 'Widget updated in layout successfully'
        ]);
    }

    /**
     * Remove widget from layout
     */
    public function removeWidgetFromLayout(DashboardLayout $layout, DashboardWidget $widget): JsonResponse
    {
        $layout->removeWidget($widget->id);

        return response()->json([
            'success' => true,
            'message' => 'Widget removed from layout successfully'
        ]);
    }

    // Dashboard Statistics and Analytics

    /**
     * Get dashboard statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'layouts' => [
                'total' => DashboardLayout::count(),
                'public' => DashboardLayout::public()->count(),
                'private' => DashboardLayout::where('is_public', false)->count(),
                'default' => DashboardLayout::default()->count()
            ],
            'widgets' => [
                'total' => DashboardWidget::count(),
                'active' => DashboardWidget::active()->count(),
                'inactive' => DashboardWidget::where('is_active', false)->count(),
                'public' => DashboardWidget::public()->count(),
                'real_time' => DashboardWidget::realTime()->count(),
                'by_type' => DashboardWidget::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // Legacy methods for backward compatibility

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->layouts($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->createLayout($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $layout = DashboardLayout::findOrFail($id);
        return $this->showLayout($layout);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $layout = DashboardLayout::findOrFail($id);
        return $this->updateLayout($request, $layout);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $layout = DashboardLayout::findOrFail($id);
        return $this->destroyLayout($layout);
    }
}
