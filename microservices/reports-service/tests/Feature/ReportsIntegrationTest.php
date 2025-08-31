<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\DashboardWidget;
use App\Models\GeneratedReport;
use App\Models\ReportTemplate;
use App\Services\AlertService;
use App\Services\AnalyticsService;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

class ReportsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $reportService;
    protected $dashboardService;
    protected $analyticsService;
    protected $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportService = app(ReportService::class);
        $this->dashboardService = app(DashboardService::class);
        $this->analyticsService = app(AnalyticsService::class);
        $this->alertService = app(AlertService::class);
        
        Storage::fake('reports');
        Queue::fake();
    }

    /** @test */
    public function it_can_create_complete_reporting_workflow()
    {
        // Step 1: Create a report template
        $templateData = [
            'name' => 'Monthly Sales Report',
            'description' => 'Comprehensive monthly sales analysis',
            'type' => 'scheduled',
            'format' => 'pdf',
            'data_sources' => ['sales', 'customers', 'products'],
            'parameters' => [
                'date_range' => 'last_month',
                'include_charts' => true,
                'group_by' => 'category'
            ],
            'schedule' => [
                'frequency' => 'monthly',
                'day_of_month' => 1,
                'time' => '09:00'
            ],
            'recipients' => [
                'emails' => ['manager@example.com', 'sales@example.com'],
                'webhooks' => ['https://api.example.com/reports']
            ]
        ];

        $template = $this->reportService->createTemplate($templateData);
        
        $this->assertInstanceOf(ReportTemplate::class, $template);
        $this->assertEquals('Monthly Sales Report', $template->name);
        $this->assertEquals('scheduled', $template->type);
        $this->assertDatabaseHas('report_templates', [
            'name' => 'Monthly Sales Report',
            'type' => 'scheduled'
        ]);

        // Step 2: Generate a report from the template
        $reportParameters = [
            'start_date' => Carbon::now()->subMonth()->startOfMonth(),
            'end_date' => Carbon::now()->subMonth()->endOfMonth(),
            'include_charts' => true
        ];

        $generatedReport = $this->reportService->generateReport($template, $reportParameters);
        
        $this->assertInstanceOf(GeneratedReport::class, $generatedReport);
        $this->assertEquals($template->id, $generatedReport->template_id);
        $this->assertEquals('completed', $generatedReport->status);
        $this->assertNotNull($generatedReport->file_path);
        $this->assertDatabaseHas('generated_reports', [
            'template_id' => $template->id,
            'status' => 'completed'
        ]);

        // Step 3: Create a dashboard with widgets
        $dashboardData = [
            'name' => 'Sales Dashboard',
            'description' => 'Real-time sales monitoring',
            'layout' => [
                'columns' => 12,
                'rows' => 8,
                'grid_size' => 'medium'
            ],
            'is_default' => false,
            'is_public' => true
        ];

        $dashboard = $this->dashboardService->createDashboard($dashboardData);
        
        $this->assertDatabaseHas('dashboards', [
            'name' => 'Sales Dashboard',
            'is_public' => true
        ]);

        // Step 4: Add widgets to the dashboard
        $widgetConfigs = [
            [
                'name' => 'Total Sales',
                'type' => 'metric',
                'data_source' => 'sales',
                'configuration' => [
                    'metric' => 'total_revenue',
                    'time_period' => 'today',
                    'format' => 'currency'
                ],
                'position' => ['x' => 0, 'y' => 0, 'width' => 3, 'height' => 2]
            ],
            [
                'name' => 'Sales Trend',
                'type' => 'line_chart',
                'data_source' => 'sales',
                'configuration' => [
                    'metric' => 'daily_revenue',
                    'time_period' => 'last_30_days',
                    'group_by' => 'date'
                ],
                'position' => ['x' => 3, 'y' => 0, 'width' => 6, 'height' => 4]
            ],
            [
                'name' => 'Top Products',
                'type' => 'table',
                'data_source' => 'products',
                'configuration' => [
                    'columns' => ['name', 'sales_count', 'revenue'],
                    'sort_by' => 'revenue',
                    'limit' => 10
                ],
                'position' => ['x' => 9, 'y' => 0, 'width' => 3, 'height' => 4]
            ]
        ];

        $widgets = [];
        foreach ($widgetConfigs as $widgetConfig) {
            $widget = $this->dashboardService->createWidget($dashboard, $widgetConfig);
            $widgets[] = $widget;
            
            $this->assertInstanceOf(DashboardWidget::class, $widget);
            $this->assertEquals($dashboard->id, $widget->dashboard_id);
            $this->assertDatabaseHas('dashboard_widgets', [
                'dashboard_id' => $dashboard->id,
                'name' => $widgetConfig['name']
            ]);
        }

        // Step 5: Create alerts for monitoring
        $alertConfigs = [
            [
                'name' => 'Low Daily Sales Alert',
                'description' => 'Alert when daily sales drop below threshold',
                'type' => 'threshold',
                'metric' => 'daily_revenue',
                'conditions' => [
                    'operator' => 'less_than',
                    'value' => 10000,
                    'time_window' => '1d'
                ],
                'notification_channels' => ['email'],
                'notification_config' => [
                    'email' => [
                        'recipients' => ['alerts@example.com'],
                        'subject' => 'Low Daily Sales Alert'
                    ]
                ],
                'severity' => 'warning',
                'is_active' => true
            ],
            [
                'name' => 'Sales Anomaly Detection',
                'description' => 'Detect unusual patterns in sales data',
                'type' => 'anomaly',
                'metric' => 'hourly_sales',
                'conditions' => [
                    'sensitivity' => 'medium',
                    'baseline_period' => '7d',
                    'deviation_threshold' => 2.5
                ],
                'notification_channels' => ['email', 'webhook'],
                'notification_config' => [
                    'email' => [
                        'recipients' => ['data-team@example.com'],
                        'subject' => 'Sales Anomaly Detected'
                    ],
                    'webhook' => [
                        'url' => 'https://hooks.slack.com/anomaly-alerts',
                        'method' => 'POST'
                    ]
                ],
                'severity' => 'critical',
                'is_active' => true
            ]
        ];

        $alerts = [];
        foreach ($alertConfigs as $alertConfig) {
            $alert = $this->alertService->createAlert($alertConfig);
            $alerts[] = $alert;
            
            $this->assertInstanceOf(Alert::class, $alert);
            $this->assertTrue($alert->is_active);
            $this->assertDatabaseHas('alerts', [
                'name' => $alertConfig['name'],
                'type' => $alertConfig['type']
            ]);
        }

        // Step 6: Test analytics data retrieval
        $kpiData = $this->analyticsService->getKPIs([
            'metrics' => ['total_revenue', 'total_orders', 'conversion_rate'],
            'time_period' => 'last_30_days',
            'compare_to' => 'previous_period'
        ]);

        $this->assertIsArray($kpiData);
        $this->assertArrayHasKey('metrics', $kpiData);
        $this->assertArrayHasKey('comparisons', $kpiData);

        // Step 7: Test dashboard data aggregation
        $dashboardData = $this->dashboardService->getDashboardData($dashboard);
        
        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('widgets', $dashboardData);
        $this->assertCount(3, $dashboardData['widgets']);

        // Step 8: Test report export functionality
        $exportData = $this->reportService->exportReport($generatedReport, 'json');
        
        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('report_data', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);

        // Verify all components work together
        $this->assertEquals(1, ReportTemplate::count());
        $this->assertEquals(1, GeneratedReport::count());
        $this->assertEquals(1, $dashboard->widgets()->count());
        $this->assertEquals(2, Alert::count());
    }

    /** @test */
    public function it_can_handle_scheduled_report_generation()
    {
        // Create a scheduled template
        $template = ReportTemplate::factory()->create([
            'type' => 'scheduled',
            'schedule' => [
                'frequency' => 'daily',
                'time' => '08:00'
            ],
            'is_active' => true
        ]);

        // Simulate scheduled job execution
        $job = new \App\Jobs\GenerateScheduledReport($template->id, [], []);
        $job->handle();

        // Verify report was generated
        $this->assertDatabaseHas('generated_reports', [
            'template_id' => $template->id,
            'status' => 'completed'
        ]);

        // Verify template's last_generated_at was updated
        $template->refresh();
        $this->assertNotNull($template->last_generated_at);
    }

    /** @test */
    public function it_can_handle_real_time_dashboard_updates()
    {
        $dashboard = $this->dashboardService->createDashboard([
            'name' => 'Real-time Dashboard',
            'refresh_interval' => 30
        ]);

        $widget = $this->dashboardService->createWidget($dashboard, [
            'name' => 'Live Metrics',
            'type' => 'metric',
            'data_source' => 'analytics',
            'configuration' => [
                'metric' => 'active_users',
                'real_time' => true
            ]
        ]);

        // Test real-time data retrieval
        $realTimeData = $this->dashboardService->getWidgetData($widget, ['real_time' => true]);
        
        $this->assertIsArray($realTimeData);
        $this->assertArrayHasKey('value', $realTimeData);
        $this->assertArrayHasKey('timestamp', $realTimeData);
    }

    /** @test */
    public function it_can_handle_alert_evaluation_and_notification()
    {
        $alert = Alert::factory()->create([
            'type' => 'threshold',
            'metric' => 'error_rate',
            'conditions' => [
                'operator' => 'greater_than',
                'value' => 5.0
            ],
            'is_active' => true
        ]);

        // Mock analytics service to return high error rate
        $this->mock(AnalyticsService::class, function ($mock) {
            $mock->shouldReceive('getRealTimeData')
                ->with('error_rate', \Mockery::any())
                ->andReturn(['value' => 7.5, 'timestamp' => now()]);
        });

        // Evaluate alert
        $result = $this->alertService->evaluateCondition($alert);
        
        $this->assertTrue($result['triggered']);
        $this->assertEquals(7.5, $result['current_value']);
        $this->assertEquals(5.0, $result['threshold_value']);
    }

    /** @test */
    public function it_can_handle_data_export_across_services()
    {
        // Create test data
        $template = ReportTemplate::factory()->create();
        $report = GeneratedReport::factory()->create(['template_id' => $template->id]);
        $dashboard = $this->dashboardService->createDashboard(['name' => 'Test Dashboard']);
        
        // Test report export
        $reportExport = $this->reportService->exportReport($report, 'csv');
        $this->assertIsArray($reportExport);
        
        // Test analytics export
        $analyticsExport = $this->analyticsService->exportData([
            'metrics' => ['revenue', 'orders'],
            'format' => 'json',
            'date_range' => ['start' => '2024-01-01', 'end' => '2024-01-31']
        ]);
        $this->assertIsArray($analyticsExport);
        
        // Test dashboard export
        $dashboardExport = $this->dashboardService->exportDashboard($dashboard);
        $this->assertIsArray($dashboardExport);
        $this->assertArrayHasKey('dashboard', $dashboardExport);
        $this->assertArrayHasKey('widgets', $dashboardExport);
    }

    /** @test */
    public function it_can_handle_error_scenarios_gracefully()
    {
        // Test invalid template data
        $this->expectException(\InvalidArgumentException::class);
        $this->reportService->createTemplate([
            'name' => '', // Invalid empty name
            'type' => 'invalid_type' // Invalid type
        ]);
    }

    /** @test */
    public function it_can_handle_concurrent_operations()
    {
        $template = ReportTemplate::factory()->create();
        
        // Simulate concurrent report generation
        $reports = [];
        for ($i = 0; $i < 3; $i++) {
            $reports[] = $this->reportService->generateReport($template, [
                'batch_id' => 'concurrent_test_' . $i
            ]);
        }
        
        // Verify all reports were created successfully
        $this->assertCount(3, $reports);
        foreach ($reports as $report) {
            $this->assertInstanceOf(GeneratedReport::class, $report);
            $this->assertEquals('completed', $report->status);
        }
    }

    /** @test */
    public function it_can_cleanup_expired_data()
    {
        // Create old reports
        $oldReports = GeneratedReport::factory()->count(5)->create([
            'created_at' => Carbon::now()->subDays(35),
            'status' => 'completed'
        ]);
        
        // Create recent reports
        $recentReports = GeneratedReport::factory()->count(3)->create([
            'created_at' => Carbon::now()->subDays(5),
            'status' => 'completed'
        ]);
        
        // Run cleanup job
        $cleanupJob = new \App\Jobs\CleanupExpiredReports();
        $cleanupJob->handle();
        
        // Verify old reports were cleaned up
        $this->assertEquals(3, GeneratedReport::count());
        
        // Verify recent reports remain
        foreach ($recentReports as $report) {
            $this->assertDatabaseHas('generated_reports', ['id' => $report->id]);
        }
    }

    /** @test */
    public function it_maintains_data_consistency_across_operations()
    {
        // Create interconnected data
        $template = ReportTemplate::factory()->create();
        $dashboard = $this->dashboardService->createDashboard(['name' => 'Consistency Test']);
        $alert = Alert::factory()->create(['metric' => 'test_metric']);
        
        // Generate report
        $report = $this->reportService->generateReport($template, []);
        
        // Update dashboard
        $widget = $this->dashboardService->createWidget($dashboard, [
            'name' => 'Test Widget',
            'type' => 'metric',
            'data_source' => 'reports'
        ]);
        
        // Verify relationships are maintained
        $this->assertEquals($template->id, $report->template_id);
        $this->assertEquals($dashboard->id, $widget->dashboard_id);
        
        // Test cascading updates
        $template->update(['name' => 'Updated Template']);
        $report->refresh();
        
        // Verify data integrity
        $this->assertDatabaseHas('report_templates', [
            'id' => $template->id,
            'name' => 'Updated Template'
        ]);
        $this->assertDatabaseHas('generated_reports', [
            'id' => $report->id,
            'template_id' => $template->id
        ]);
    }
}