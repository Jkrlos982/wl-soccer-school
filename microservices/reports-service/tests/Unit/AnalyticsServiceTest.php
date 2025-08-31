<?php

namespace Tests\Unit;

use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Carbon\Carbon;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = new AnalyticsService();
        Cache::flush();
        
        // Mock some test data in database
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        // Create test tables and data for analytics
        DB::statement('CREATE TABLE IF NOT EXISTS test_users (
            id INTEGER PRIMARY KEY,
            email VARCHAR(255),
            created_at DATETIME,
            last_login_at DATETIME,
            is_active BOOLEAN DEFAULT 1
        )');
        
        DB::statement('CREATE TABLE IF NOT EXISTS test_sessions (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            started_at DATETIME,
            ended_at DATETIME,
            page_views INTEGER DEFAULT 0
        )');
        
        DB::statement('CREATE TABLE IF NOT EXISTS test_orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            amount DECIMAL(10,2),
            status VARCHAR(50),
            created_at DATETIME
        )');

        // Insert test data
        $now = Carbon::now();
        
        // Users data
        for ($i = 1; $i <= 100; $i++) {
            DB::table('test_users')->insert([
                'id' => $i,
                'email' => "user{$i}@example.com",
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'last_login_at' => $now->copy()->subDays(rand(0, 7)),
                'is_active' => rand(0, 1)
            ]);
        }
        
        // Sessions data
        for ($i = 1; $i <= 200; $i++) {
            $startTime = $now->copy()->subDays(rand(0, 30));
            DB::table('test_sessions')->insert([
                'id' => $i,
                'user_id' => rand(1, 100),
                'started_at' => $startTime,
                'ended_at' => $startTime->copy()->addMinutes(rand(5, 120)),
                'page_views' => rand(1, 20)
            ]);
        }
        
        // Orders data
        for ($i = 1; $i <= 50; $i++) {
            DB::table('test_orders')->insert([
                'id' => $i,
                'user_id' => rand(1, 100),
                'amount' => rand(10, 500),
                'status' => ['completed', 'pending', 'cancelled'][rand(0, 2)],
                'created_at' => $now->copy()->subDays(rand(0, 30))
            ]);
        }
    }

    /** @test */
    public function it_can_get_kpis()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'metrics' => ['users', 'sessions', 'revenue']
        ];

        $kpis = $this->analyticsService->getKPIs($parameters);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_users', $kpis);
        $this->assertArrayHasKey('active_users', $kpis);
        $this->assertArrayHasKey('total_sessions', $kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('conversion_rate', $kpis);
        $this->assertArrayHasKey('retention_rate', $kpis);
        
        $this->assertIsNumeric($kpis['total_users']);
        $this->assertIsNumeric($kpis['conversion_rate']);
        $this->assertTrue($kpis['conversion_rate'] >= 0 && $kpis['conversion_rate'] <= 100);
    }

    /** @test */
    public function it_can_get_trend_analysis()
    {
        $parameters = [
            'metric' => 'users',
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'granularity' => 'daily'
        ];

        $trends = $this->analyticsService->getTrendAnalysis($parameters);

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('current_period', $trends);
        $this->assertArrayHasKey('previous_period', $trends);
        $this->assertArrayHasKey('trend_direction', $trends);
        $this->assertArrayHasKey('percentage_change', $trends);
        $this->assertArrayHasKey('data_points', $trends);
        
        $this->assertContains($trends['trend_direction'], ['up', 'down', 'stable']);
        $this->assertIsArray($trends['data_points']);
    }

    /** @test */
    public function it_can_get_comparison_analysis()
    {
        $parameters = [
            'metrics' => ['users', 'sessions'],
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'compare_to' => 'previous_period'
        ];

        $comparison = $this->analyticsService->getComparisonAnalysis($parameters);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('current_period', $comparison);
        $this->assertArrayHasKey('comparison_period', $comparison);
        $this->assertArrayHasKey('changes', $comparison);
        
        foreach ($parameters['metrics'] as $metric) {
            $this->assertArrayHasKey($metric, $comparison['changes']);
            $this->assertArrayHasKey('absolute', $comparison['changes'][$metric]);
            $this->assertArrayHasKey('percentage', $comparison['changes'][$metric]);
        }
    }

    /** @test */
    public function it_can_get_distribution_analysis()
    {
        $parameters = [
            'metric' => 'page_views',
            'dimension' => 'user_segment',
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        $distribution = $this->analyticsService->getDistributionAnalysis($parameters);

        $this->assertIsArray($distribution);
        $this->assertArrayHasKey('total', $distribution);
        $this->assertArrayHasKey('segments', $distribution);
        $this->assertArrayHasKey('percentages', $distribution);
        
        $this->assertIsArray($distribution['segments']);
        $this->assertIsArray($distribution['percentages']);
    }

    /** @test */
    public function it_can_get_dashboard_summary()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(7)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        $summary = $this->analyticsService->getDashboardSummary($parameters);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('key_metrics', $summary);
        $this->assertArrayHasKey('trends', $summary);
        $this->assertArrayHasKey('alerts', $summary);
        $this->assertArrayHasKey('recent_activity', $summary);
        
        $this->assertIsArray($summary['key_metrics']);
        $this->assertIsArray($summary['trends']);
    }

    /** @test */
    public function it_can_get_metadata()
    {
        $metadata = $this->analyticsService->getMetadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('available_metrics', $metadata);
        $this->assertArrayHasKey('available_dimensions', $metadata);
        $this->assertArrayHasKey('date_ranges', $metadata);
        $this->assertArrayHasKey('granularities', $metadata);
        
        $this->assertIsArray($metadata['available_metrics']);
        $this->assertContains('users', $metadata['available_metrics']);
        $this->assertContains('sessions', $metadata['available_metrics']);
    }

    /** @test */
    public function it_can_calculate_conversion_rate()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        $conversionRate = $this->analyticsService->calculateConversionRate($parameters);

        $this->assertIsFloat($conversionRate);
        $this->assertTrue($conversionRate >= 0 && $conversionRate <= 100);
    }

    /** @test */
    public function it_can_calculate_retention_rate()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'cohort_period' => 'weekly'
        ];

        $retentionRate = $this->analyticsService->calculateRetentionRate($parameters);

        $this->assertIsFloat($retentionRate);
        $this->assertTrue($retentionRate >= 0 && $retentionRate <= 100);
    }

    /** @test */
    public function it_can_calculate_churn_rate()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        $churnRate = $this->analyticsService->calculateChurnRate($parameters);

        $this->assertIsFloat($churnRate);
        $this->assertTrue($churnRate >= 0 && $churnRate <= 100);
    }

    /** @test */
    public function it_can_get_real_time_data()
    {
        Http::fake([
            'analytics-api.example.com/*' => Http::response([
                'active_users' => 42,
                'current_sessions' => 67,
                'page_views_per_minute' => 156,
                'timestamp' => Carbon::now()->toISOString()
            ], 200)
        ]);

        $realTimeData = $this->analyticsService->getRealTimeData('live_metrics');

        $this->assertIsArray($realTimeData);
        $this->assertArrayHasKey('active_users', $realTimeData);
        $this->assertArrayHasKey('current_sessions', $realTimeData);
        $this->assertArrayHasKey('timestamp', $realTimeData);
    }

    /** @test */
    public function it_caches_analytics_data()
    {
        $parameters = [
            'date_range' => [
                'start' => Carbon::now()->subDays(7)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        // First call should cache the data
        $data1 = $this->analyticsService->getKPIs($parameters);
        
        // Second call should return cached data
        $data2 = $this->analyticsService->getKPIs($parameters);

        $this->assertEquals($data1, $data2);
        
        // Verify cache key exists
        $cacheKey = $this->analyticsService->generateCacheKey('kpis', $parameters);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_can_clear_analytics_cache()
    {
        $parameters = ['metric' => 'users'];
        $cacheKey = $this->analyticsService->generateCacheKey('test', $parameters);
        
        // Set cache
        Cache::put($cacheKey, ['test_data' => true], 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        $this->analyticsService->clearCache('test', $parameters);
        
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_handles_invalid_date_ranges()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date range');

        $parameters = [
            'date_range' => [
                'start' => '2024-12-31',
                'end' => '2024-01-01' // End before start
            ]
        ];

        $this->analyticsService->getKPIs($parameters);
    }

    /** @test */
    public function it_handles_missing_required_parameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date range is required');

        // Missing date_range parameter
        $this->analyticsService->getKPIs([]);
    }

    /** @test */
    public function it_can_generate_forecast()
    {
        $historicalData = [
            ['date' => '2024-01-01', 'value' => 100],
            ['date' => '2024-01-02', 'value' => 110],
            ['date' => '2024-01-03', 'value' => 105],
            ['date' => '2024-01-04', 'value' => 120],
            ['date' => '2024-01-05', 'value' => 115]
        ];

        $forecast = $this->analyticsService->generateForecast($historicalData, 3);

        $this->assertIsArray($forecast);
        $this->assertCount(3, $forecast);
        
        foreach ($forecast as $point) {
            $this->assertArrayHasKey('date', $point);
            $this->assertArrayHasKey('predicted_value', $point);
            $this->assertArrayHasKey('confidence_interval', $point);
        }
    }

    /** @test */
    public function it_can_calculate_trend_direction()
    {
        // Test upward trend
        $upwardData = [100, 110, 120, 130, 140];
        $upwardTrend = $this->analyticsService->calculateTrend($upwardData);
        $this->assertEquals('up', $upwardTrend['direction']);
        $this->assertTrue($upwardTrend['percentage_change'] > 0);

        // Test downward trend
        $downwardData = [140, 130, 120, 110, 100];
        $downwardTrend = $this->analyticsService->calculateTrend($downwardData);
        $this->assertEquals('down', $downwardTrend['direction']);
        $this->assertTrue($downwardTrend['percentage_change'] < 0);

        // Test stable trend
        $stableData = [100, 101, 99, 100, 102];
        $stableTrend = $this->analyticsService->calculateTrend($stableData);
        $this->assertEquals('stable', $stableTrend['direction']);
    }

    /** @test */
    public function it_can_export_analytics_data()
    {
        $parameters = [
            'metrics' => ['users', 'sessions'],
            'date_range' => [
                'start' => Carbon::now()->subDays(7)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'format' => 'csv'
        ];

        $exportData = $this->analyticsService->exportData($parameters);

        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('filename', $exportData);
        $this->assertArrayHasKey('content', $exportData);
        $this->assertArrayHasKey('mime_type', $exportData);
        
        $this->assertStringContainsString('.csv', $exportData['filename']);
        $this->assertEquals('text/csv', $exportData['mime_type']);
    }

    /** @test */
    public function it_validates_metric_availability()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid metric: invalid_metric');

        $parameters = [
            'metrics' => ['invalid_metric'],
            'date_range' => [
                'start' => Carbon::now()->subDays(7)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ]
        ];

        $this->analyticsService->getKPIs($parameters);
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        DB::statement('DROP TABLE IF EXISTS test_users');
        DB::statement('DROP TABLE IF EXISTS test_sessions');
        DB::statement('DROP TABLE IF EXISTS test_orders');
        
        parent::tearDown();
    }
}