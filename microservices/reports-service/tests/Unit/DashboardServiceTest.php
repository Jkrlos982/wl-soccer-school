<?php

namespace Tests\Unit;

use App\Models\DashboardLayout;
use App\Models\DashboardWidget;
use App\Services\DashboardService;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $dashboardService;
    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = Mockery::mock(AnalyticsService::class);
        $this->dashboardService = new DashboardService($this->analyticsService);
        
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_dashboard_layout()
    {
        $layoutData = [
            'name' => 'Test Dashboard',
            'description' => 'A test dashboard layout',
            'layout_config' => [
                'columns' => 12,
                'rows' => 8,
                'gap' => 16
            ],
            'is_default' => false,
            'is_public' => true
        ];

        $layout = $this->dashboardService->createLayout($layoutData);

        $this->assertInstanceOf(DashboardLayout::class, $layout);
        $this->assertEquals('Test Dashboard', $layout->name);
        $this->assertEquals('A test dashboard layout', $layout->description);
        $this->assertFalse($layout->is_default);
        $this->assertTrue($layout->is_public);
        $this->assertDatabaseHas('dashboard_layouts', [
            'name' => 'Test Dashboard',
            'is_public' => true
        ]);
    }

    /** @test */
    public function it_can_update_dashboard_layout()
    {
        $layout = DashboardLayout::factory()->create([
            'name' => 'Original Layout',
            'description' => 'Original Description'
        ]);

        $updateData = [
            'name' => 'Updated Layout',
            'description' => 'Updated Description',
            'is_public' => false
        ];

        $updatedLayout = $this->dashboardService->updateLayout($layout, $updateData);

        $this->assertEquals('Updated Layout', $updatedLayout->name);
        $this->assertEquals('Updated Description', $updatedLayout->description);
        $this->assertFalse($updatedLayout->is_public);
        $this->assertDatabaseHas('dashboard_layouts', [
            'id' => $layout->id,
            'name' => 'Updated Layout'
        ]);
    }

    /** @test */
    public function it_can_duplicate_dashboard_layout()
    {
        $originalLayout = DashboardLayout::factory()->create([
            'name' => 'Original Layout',
            'description' => 'Original Description'
        ]);

        // Add some widgets to the original layout
        DashboardWidget::factory()->count(3)->create([
            'layout_id' => $originalLayout->id
        ]);

        $duplicatedLayout = $this->dashboardService->duplicateLayout($originalLayout);

        $this->assertNotEquals($originalLayout->id, $duplicatedLayout->id);
        $this->assertEquals('Original Layout (Copy)', $duplicatedLayout->name);
        $this->assertEquals('Original Description', $duplicatedLayout->description);
        $this->assertEquals(3, $duplicatedLayout->widgets()->count());
        $this->assertDatabaseHas('dashboard_layouts', [
            'name' => 'Original Layout (Copy)'
        ]);
    }

    /** @test */
    public function it_can_set_default_layout()
    {
        // Create multiple layouts
        $layout1 = DashboardLayout::factory()->create(['is_default' => true]);
        $layout2 = DashboardLayout::factory()->create(['is_default' => false]);

        $this->dashboardService->setDefaultLayout($layout2);

        $layout1->refresh();
        $layout2->refresh();

        $this->assertFalse($layout1->is_default);
        $this->assertTrue($layout2->is_default);
    }

    /** @test */
    public function it_can_create_widget()
    {
        $layout = DashboardLayout::factory()->create();
        
        $widgetData = [
            'layout_id' => $layout->id,
            'name' => 'Test Widget',
            'type' => 'chart',
            'data_source' => 'analytics',
            'config' => [
                'chart_type' => 'line',
                'metrics' => ['users', 'sessions'],
                'time_range' => 'last_30_days'
            ],
            'position' => [
                'x' => 0,
                'y' => 0,
                'width' => 6,
                'height' => 4
            ],
            'refresh_interval' => 300
        ];

        $widget = $this->dashboardService->createWidget($widgetData);

        $this->assertInstanceOf(DashboardWidget::class, $widget);
        $this->assertEquals('Test Widget', $widget->name);
        $this->assertEquals('chart', $widget->type);
        $this->assertEquals($layout->id, $widget->layout_id);
        $this->assertEquals(300, $widget->refresh_interval);
        $this->assertDatabaseHas('dashboard_widgets', [
            'name' => 'Test Widget',
            'type' => 'chart'
        ]);
    }

    /** @test */
    public function it_can_update_widget()
    {
        $widget = DashboardWidget::factory()->create([
            'name' => 'Original Widget',
            'type' => 'metric'
        ]);

        $updateData = [
            'name' => 'Updated Widget',
            'type' => 'chart',
            'config' => [
                'chart_type' => 'bar',
                'metrics' => ['revenue']
            ]
        ];

        $updatedWidget = $this->dashboardService->updateWidget($widget, $updateData);

        $this->assertEquals('Updated Widget', $updatedWidget->name);
        $this->assertEquals('chart', $updatedWidget->type);
        $this->assertEquals('bar', $updatedWidget->config['chart_type']);
        $this->assertDatabaseHas('dashboard_widgets', [
            'id' => $widget->id,
            'name' => 'Updated Widget'
        ]);
    }

    /** @test */
    public function it_can_get_widget_data()
    {
        $widget = DashboardWidget::factory()->create([
            'type' => 'chart',
            'data_source' => 'analytics',
            'config' => [
                'metrics' => ['users', 'sessions'],
                'time_range' => 'last_7_days'
            ]
        ]);

        // Mock analytics service response
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('analytics', Mockery::any())
            ->andReturn([
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'datasets' => [
                    [
                        'label' => 'Users',
                        'data' => [100, 120, 110, 130, 140, 160, 150]
                    ],
                    [
                        'label' => 'Sessions',
                        'data' => [150, 180, 165, 195, 210, 240, 225]
                    ]
                ]
            ]);

        $widgetData = $this->dashboardService->getWidgetData($widget);

        $this->assertArrayHasKey('labels', $widgetData);
        $this->assertArrayHasKey('datasets', $widgetData);
        $this->assertCount(7, $widgetData['labels']);
        $this->assertCount(2, $widgetData['datasets']);
    }

    /** @test */
    public function it_can_get_real_time_widget_data()
    {
        $widget = DashboardWidget::factory()->create([
            'type' => 'realtime',
            'data_source' => 'live_metrics',
            'config' => [
                'metrics' => ['active_users', 'current_sessions']
            ]
        ]);

        // Mock real-time data
        $this->analyticsService->shouldReceive('getRealTimeData')
            ->with('live_metrics', Mockery::any())
            ->andReturn([
                'active_users' => 45,
                'current_sessions' => 67,
                'timestamp' => now()->toISOString()
            ]);

        $realTimeData = $this->dashboardService->getRealTimeWidgetData($widget);

        $this->assertArrayHasKey('active_users', $realTimeData);
        $this->assertArrayHasKey('current_sessions', $realTimeData);
        $this->assertArrayHasKey('timestamp', $realTimeData);
        $this->assertEquals(45, $realTimeData['active_users']);
        $this->assertEquals(67, $realTimeData['current_sessions']);
    }

    /** @test */
    public function it_caches_widget_data()
    {
        $widget = DashboardWidget::factory()->create([
            'type' => 'chart',
            'data_source' => 'analytics',
            'config' => ['metrics' => ['users']],
            'refresh_interval' => 300
        ]);

        $mockData = [
            'labels' => ['Day 1', 'Day 2'],
            'data' => [100, 120]
        ];

        // Mock should be called only once due to caching
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->once()
            ->andReturn($mockData);

        // First call - should hit the service
        $data1 = $this->dashboardService->getWidgetData($widget);
        
        // Second call - should hit the cache
        $data2 = $this->dashboardService->getWidgetData($widget);

        $this->assertEquals($data1, $data2);
        $this->assertEquals($mockData, $data1);
    }

    /** @test */
    public function it_can_refresh_widget_cache()
    {
        $widget = DashboardWidget::factory()->create([
            'type' => 'metric',
            'data_source' => 'analytics'
        ]);

        // Set initial cache
        $cacheKey = "widget_data_{$widget->id}";
        Cache::put($cacheKey, ['old_data' => true], 300);

        $newData = ['new_data' => true, 'updated_at' => now()];
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->once()
            ->andReturn($newData);

        $refreshedData = $this->dashboardService->refreshWidget($widget);

        $this->assertEquals($newData, $refreshedData);
        $this->assertEquals($newData, Cache::get($cacheKey));
    }

    /** @test */
    public function it_can_get_dashboard_statistics()
    {
        // Create test data
        $layout = DashboardLayout::factory()->create();
        DashboardWidget::factory()->count(5)->create(['layout_id' => $layout->id]);
        DashboardWidget::factory()->count(3)->create(); // Different layout

        $statistics = $this->dashboardService->getDashboardStatistics();

        $this->assertArrayHasKey('total_layouts', $statistics);
        $this->assertArrayHasKey('total_widgets', $statistics);
        $this->assertArrayHasKey('widgets_by_type', $statistics);
        $this->assertArrayHasKey('layouts_by_user', $statistics);
        $this->assertEquals(8, $statistics['total_widgets']);
    }

    /** @test */
    public function it_handles_widget_data_source_errors()
    {
        $widget = DashboardWidget::factory()->create([
            'data_source' => 'invalid_source'
        ]);

        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->andThrow(new \Exception('Data source not available'));

        $widgetData = $this->dashboardService->getWidgetData($widget);

        $this->assertArrayHasKey('error', $widgetData);
        $this->assertEquals('Data source not available', $widgetData['error']);
    }

    /** @test */
    public function it_validates_widget_position()
    {
        $layout = DashboardLayout::factory()->create([
            'layout_config' => [
                'columns' => 12,
                'rows' => 8
            ]
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Widget position exceeds layout boundaries');

        $this->dashboardService->createWidget([
            'layout_id' => $layout->id,
            'name' => 'Invalid Widget',
            'type' => 'chart',
            'position' => [
                'x' => 10,
                'y' => 0,
                'width' => 5, // This would exceed 12 columns
                'height' => 4
            ]
        ]);
    }

    /** @test */
    public function it_can_duplicate_widget()
    {
        $originalWidget = DashboardWidget::factory()->create([
            'name' => 'Original Widget',
            'type' => 'chart',
            'config' => ['metric' => 'users']
        ]);

        $duplicatedWidget = $this->dashboardService->duplicateWidget($originalWidget);

        $this->assertNotEquals($originalWidget->id, $duplicatedWidget->id);
        $this->assertEquals('Original Widget (Copy)', $duplicatedWidget->name);
        $this->assertEquals('chart', $duplicatedWidget->type);
        $this->assertEquals(['metric' => 'users'], $duplicatedWidget->config);
        $this->assertDatabaseHas('dashboard_widgets', [
            'name' => 'Original Widget (Copy)'
        ]);
    }

    /** @test */
    public function it_can_get_layout_with_widgets()
    {
        $layout = DashboardLayout::factory()->create();
        $widgets = DashboardWidget::factory()->count(3)->create([
            'layout_id' => $layout->id
        ]);

        $layoutWithWidgets = $this->dashboardService->getLayoutWithWidgets($layout->id);

        $this->assertEquals($layout->id, $layoutWithWidgets->id);
        $this->assertCount(3, $layoutWithWidgets->widgets);
        $this->assertEquals($widgets->pluck('id')->sort(), $layoutWithWidgets->widgets->pluck('id')->sort());
    }

    /** @test */
    public function it_can_clear_widget_cache()
    {
        $widget = DashboardWidget::factory()->create();
        $cacheKey = "widget_data_{$widget->id}";
        
        // Set cache
        Cache::put($cacheKey, ['cached_data' => true], 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        $this->dashboardService->clearWidgetCache($widget);
        
        $this->assertFalse(Cache::has($cacheKey));
    }
}