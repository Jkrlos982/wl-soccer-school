<?php

namespace Tests\Unit;

use App\Models\ReportTemplate;
use App\Models\GeneratedReport;
use App\Services\ReportService;
use App\Services\AnalyticsService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reportService;
    protected $analyticsService;
    protected $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = Mockery::mock(AnalyticsService::class);
        $this->dashboardService = Mockery::mock(DashboardService::class);
        
        $this->reportService = new ReportService(
            $this->analyticsService,
            $this->dashboardService
        );
        
        Storage::fake('reports');
        Storage::fake('temp');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_report_template()
    {
        $templateData = [
            'name' => 'Test Report Template',
            'description' => 'A test report template',
            'type' => 'analytics',
            'format' => 'pdf',
            'layout' => [
                'orientation' => 'portrait',
                'margins' => ['top' => 20, 'bottom' => 20, 'left' => 15, 'right' => 15]
            ],
            'data_sources' => [
                [
                    'name' => 'users',
                    'type' => 'database',
                    'query' => 'SELECT * FROM users',
                    'parameters' => []
                ]
            ],
            'parameters' => [
                'date_range' => [
                    'type' => 'date_range',
                    'required' => true,
                    'default' => 'last_30_days'
                ]
            ],
            'schedule' => null,
            'is_active' => true
        ];

        $template = $this->reportService->createTemplate($templateData);

        $this->assertInstanceOf(ReportTemplate::class, $template);
        $this->assertEquals('Test Report Template', $template->name);
        $this->assertEquals('analytics', $template->type);
        $this->assertEquals('pdf', $template->format);
        $this->assertTrue($template->is_active);
        $this->assertDatabaseHas('report_templates', [
            'name' => 'Test Report Template',
            'type' => 'analytics'
        ]);
    }

    /** @test */
    public function it_can_update_report_template()
    {
        $template = ReportTemplate::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'format' => 'excel'
        ];

        $updatedTemplate = $this->reportService->updateTemplate($template, $updateData);

        $this->assertEquals('Updated Name', $updatedTemplate->name);
        $this->assertEquals('Updated Description', $updatedTemplate->description);
        $this->assertEquals('excel', $updatedTemplate->format);
        $this->assertDatabaseHas('report_templates', [
            'id' => $template->id,
            'name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function it_can_duplicate_report_template()
    {
        $originalTemplate = ReportTemplate::factory()->create([
            'name' => 'Original Template',
            'description' => 'Original Description'
        ]);

        $duplicatedTemplate = $this->reportService->duplicateTemplate($originalTemplate);

        $this->assertNotEquals($originalTemplate->id, $duplicatedTemplate->id);
        $this->assertEquals('Original Template (Copy)', $duplicatedTemplate->name);
        $this->assertEquals('Original Description', $duplicatedTemplate->description);
        $this->assertDatabaseHas('report_templates', [
            'name' => 'Original Template (Copy)'
        ]);
    }

    /** @test */
    public function it_can_generate_report_from_template()
    {
        $template = ReportTemplate::factory()->create([
            'data_sources' => [
                [
                    'name' => 'test_data',
                    'type' => 'analytics',
                    'source' => 'kpis',
                    'parameters' => []
                ]
            ],
            'format' => 'pdf'
        ]);

        // Mock analytics service response
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('kpis', Mockery::any())
            ->andReturn([
                'total_users' => 1000,
                'active_users' => 750,
                'conversion_rate' => 15.5
            ]);

        $parameters = [
            'date_range' => [
                'start' => '2024-01-01',
                'end' => '2024-01-31'
            ]
        ];

        $generatedReport = $this->reportService->generateReport($template, $parameters);

        $this->assertInstanceOf(GeneratedReport::class, $generatedReport);
        $this->assertEquals($template->id, $generatedReport->template_id);
        $this->assertEquals('processing', $generatedReport->status);
        $this->assertDatabaseHas('generated_reports', [
            'template_id' => $template->id,
            'status' => 'processing'
        ]);
    }

    /** @test */
    public function it_can_fetch_report_data()
    {
        $template = ReportTemplate::factory()->create([
            'data_sources' => [
                [
                    'name' => 'analytics_data',
                    'type' => 'analytics',
                    'source' => 'trends',
                    'parameters' => ['metric' => 'users']
                ],
                [
                    'name' => 'dashboard_data',
                    'type' => 'widget',
                    'source' => 'user_stats',
                    'parameters' => []
                ]
            ]
        ]);

        // Mock service responses
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('trends', Mockery::any())
            ->andReturn([
                'labels' => ['Jan', 'Feb', 'Mar'],
                'data' => [100, 150, 200]
            ]);

        $this->dashboardService->shouldReceive('getWidgetData')
            ->with('user_stats', Mockery::any())
            ->andReturn([
                'total' => 1000,
                'active' => 750,
                'inactive' => 250
            ]);

        $parameters = ['date_range' => 'last_30_days'];
        $reportData = $this->reportService->fetchReportData($template, $parameters);

        $this->assertArrayHasKey('analytics_data', $reportData);
        $this->assertArrayHasKey('dashboard_data', $reportData);
        $this->assertEquals(['Jan', 'Feb', 'Mar'], $reportData['analytics_data']['labels']);
        $this->assertEquals(1000, $reportData['dashboard_data']['total']);
    }

    /** @test */
    public function it_can_preview_report_data()
    {
        $template = ReportTemplate::factory()->create([
            'data_sources' => [
                [
                    'name' => 'sample_data',
                    'type' => 'analytics',
                    'source' => 'kpis',
                    'parameters' => []
                ]
            ]
        ]);

        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('kpis', Mockery::any())
            ->andReturn([
                'users' => array_fill(0, 100, ['id' => 1, 'name' => 'User']),
                'total_count' => 100
            ]);

        $parameters = ['date_range' => 'last_7_days'];
        $previewData = $this->reportService->previewReportData($template, $parameters, 10);

        $this->assertArrayHasKey('sample_data', $previewData);
        $this->assertArrayHasKey('preview_info', $previewData);
        $this->assertEquals(10, $previewData['preview_info']['limit']);
        $this->assertTrue($previewData['preview_info']['is_preview']);
    }

    /** @test */
    public function it_can_get_report_statistics()
    {
        // Create test data
        GeneratedReport::factory()->count(5)->create(['status' => 'completed']);
        GeneratedReport::factory()->count(2)->create(['status' => 'failed']);
        GeneratedReport::factory()->count(1)->create(['status' => 'processing']);

        $statistics = $this->reportService->getReportStatistics();

        $this->assertArrayHasKey('total_reports', $statistics);
        $this->assertArrayHasKey('by_status', $statistics);
        $this->assertArrayHasKey('by_format', $statistics);
        $this->assertEquals(8, $statistics['total_reports']);
        $this->assertEquals(5, $statistics['by_status']['completed']);
        $this->assertEquals(2, $statistics['by_status']['failed']);
    }

    /** @test */
    public function it_handles_invalid_template_data()
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidData = [
            'name' => '', // Empty name should fail validation
            'type' => 'invalid_type'
        ];

        $this->reportService->createTemplate($invalidData);
    }

    /** @test */
    public function it_handles_missing_data_sources()
    {
        $template = ReportTemplate::factory()->create([
            'data_sources' => [
                [
                    'name' => 'missing_source',
                    'type' => 'analytics',
                    'source' => 'nonexistent',
                    'parameters' => []
                ]
            ]
        ]);

        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('nonexistent', Mockery::any())
            ->andThrow(new \Exception('Data source not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data source not found');

        $this->reportService->fetchReportData($template, []);
    }

    /** @test */
    public function it_can_cancel_report_generation()
    {
        $report = GeneratedReport::factory()->create([
            'status' => 'processing'
        ]);

        $cancelledReport = $this->reportService->cancelReport($report);

        $this->assertEquals('cancelled', $cancelledReport->status);
        $this->assertNotNull($cancelledReport->cancelled_at);
        $this->assertDatabaseHas('generated_reports', [
            'id' => $report->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_can_get_download_url()
    {
        $report = GeneratedReport::factory()->create([
            'status' => 'completed',
            'file_path' => 'reports/test-report.pdf'
        ]);

        Storage::disk('reports')->put('reports/test-report.pdf', 'fake content');

        $downloadUrl = $this->reportService->getDownloadUrl($report);

        $this->assertStringContainsString('reports/test-report.pdf', $downloadUrl);
    }

    /** @test */
    public function it_validates_report_parameters()
    {
        $template = ReportTemplate::factory()->create([
            'parameters' => [
                'date_range' => [
                    'type' => 'date_range',
                    'required' => true
                ],
                'user_id' => [
                    'type' => 'integer',
                    'required' => false
                ]
            ]
        ]);

        // Test missing required parameter
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter missing: date_range');

        $this->reportService->generateReport($template, []);
    }
}
