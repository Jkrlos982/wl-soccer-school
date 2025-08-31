<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Services\AlertService;
use App\Services\AnalyticsService;
use App\Mail\AlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $alertService;
    protected $analyticsService;
    protected $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = Mockery::mock(AnalyticsService::class);
        $this->dashboardService = Mockery::mock(\App\Services\DashboardService::class);
        $this->alertService = new AlertService($this->analyticsService, $this->dashboardService);
        
        Mail::fake();
        Http::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_alert()
    {
        $alertData = [
            'name' => 'High User Activity Alert',
            'description' => 'Alert when user activity exceeds threshold',
            'type' => 'threshold',
            'metric' => 'active_users',
            'conditions' => [
                'operator' => 'greater_than',
                'value' => 1000,
                'time_window' => '5m'
            ],
            'notification_channels' => ['email', 'webhook'],
            'notification_config' => [
                'email' => [
                    'recipients' => ['admin@example.com', 'ops@example.com'],
                    'subject' => 'High User Activity Detected'
                ],
                'webhook' => [
                    'url' => 'https://hooks.slack.com/webhook',
                    'method' => 'POST'
                ]
            ],
            'is_active' => true,
            'severity' => 'warning'
        ];

        $alert = $this->alertService->createAlert($alertData);

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertEquals('High User Activity Alert', $alert->name);
        $this->assertEquals('threshold', $alert->type);
        $this->assertEquals('active_users', $alert->metric);
        $this->assertTrue($alert->is_active);
        $this->assertEquals('warning', $alert->severity);
        $this->assertDatabaseHas('alerts', [
            'name' => 'High User Activity Alert',
            'type' => 'threshold'
        ]);
    }

    /** @test */
    public function it_can_update_alert()
    {
        $alert = Alert::factory()->create([
            'name' => 'Original Alert',
            'severity' => 'info'
        ]);

        $updateData = [
            'name' => 'Updated Alert',
            'severity' => 'critical',
            'is_active' => false
        ];

        $updatedAlert = $this->alertService->updateAlert($alert, $updateData);

        $this->assertEquals('Updated Alert', $updatedAlert->name);
        $this->assertEquals('critical', $updatedAlert->severity);
        $this->assertFalse($updatedAlert->is_active);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'name' => 'Updated Alert'
        ]);
    }

    /** @test */
    public function it_can_evaluate_threshold_condition()
    {
        $alert = Alert::factory()->create([
            'type' => 'threshold',
            'metric' => 'active_users',
            'conditions' => [
                'operator' => 'greater_than',
                'value' => 500,
                'time_window' => '5m'
            ]
        ]);

        // Mock analytics service to return value above threshold
        $this->analyticsService->shouldReceive('getRealTimeData')
            ->with('active_users', Mockery::any())
            ->andReturn(['value' => 750, 'timestamp' => now()]);

        $result = $this->alertService->evaluateCondition($alert);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(750, $result['current_value']);
        $this->assertEquals(500, $result['threshold_value']);
        $this->assertEquals('greater_than', $result['operator']);
    }

    /** @test */
    public function it_can_evaluate_anomaly_detection_condition()
    {
        $alert = Alert::factory()->create([
            'type' => 'anomaly',
            'metric' => 'page_views',
            'conditions' => [
                'sensitivity' => 'medium',
                'baseline_period' => '7d',
                'deviation_threshold' => 2.0
            ]
        ]);

        // Mock historical data for baseline
        $this->analyticsService->shouldReceive('getAnalyticsData')
            ->with('page_views', Mockery::any())
            ->andReturn([
                'historical_average' => 1000,
                'standard_deviation' => 100,
                'current_value' => 1250
            ]);

        $result = $this->alertService->evaluateCondition($alert);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(1250, $result['current_value']);
        $this->assertEquals(1000, $result['baseline_average']);
        $this->assertTrue($result['deviation_score'] > 2.0);
    }

    /** @test */
    public function it_can_evaluate_trend_condition()
    {
        $alert = Alert::factory()->create([
            'type' => 'trend',
            'metric' => 'conversion_rate',
            'conditions' => [
                'trend_direction' => 'decreasing',
                'trend_threshold' => -10.0,
                'time_period' => '1h'
            ]
        ]);

        // Mock trend data
        $this->analyticsService->shouldReceive('getTrendAnalysis')
            ->with(Mockery::any())
            ->andReturn([
                'trend_direction' => 'down',
                'percentage_change' => -15.5,
                'current_value' => 2.5,
                'previous_value' => 3.0
            ]);

        $result = $this->alertService->evaluateCondition($alert);

        $this->assertTrue($result['triggered']);
        $this->assertEquals('down', $result['trend_direction']);
        $this->assertEquals(-15.5, $result['percentage_change']);
    }

    /** @test */
    public function it_can_send_email_notification()
    {
        $alert = Alert::factory()->create([
            'notification_channels' => ['email'],
            'notification_config' => [
                'email' => [
                    'recipients' => ['test@example.com'],
                    'subject' => 'Test Alert'
                ]
            ]
        ]);

        $alertData = [
            'triggered' => true,
            'current_value' => 1500,
            'threshold_value' => 1000,
            'timestamp' => now()
        ];

        $this->alertService->sendNotification($alert, $alertData);

        Mail::assertSent(AlertNotification::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function it_can_send_webhook_notification()
    {
        $alert = Alert::factory()->create([
            'notification_channels' => ['webhook'],
            'notification_config' => [
                'webhook' => [
                    'url' => 'https://hooks.example.com/webhook',
                    'method' => 'POST'
                ]
            ]
        ]);

        $alertData = [
            'triggered' => true,
            'current_value' => 1500,
            'threshold_value' => 1000,
            'timestamp' => now()
        ];

        Http::fake([
            'hooks.example.com/*' => Http::response(['status' => 'ok'], 200)
        ]);

        $this->alertService->sendNotification($alert, $alertData);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.example.com/webhook' &&
                   $request->method() === 'POST' &&
                   $request->data()['alert_name'] !== null;
        });
    }

    /** @test */
    public function it_can_check_all_alerts()
    {
        // Create multiple alerts
        $alert1 = Alert::factory()->create([
            'is_active' => true,
            'type' => 'threshold',
            'metric' => 'users',
            'conditions' => ['operator' => 'greater_than', 'value' => 100]
        ]);

        $alert2 = Alert::factory()->create([
            'is_active' => true,
            'type' => 'threshold',
            'metric' => 'sessions',
            'conditions' => ['operator' => 'less_than', 'value' => 50]
        ]);

        $alert3 = Alert::factory()->create([
            'is_active' => false // Inactive alert should be skipped
        ]);

        // Mock analytics responses
        $this->analyticsService->shouldReceive('getRealTimeData')
            ->with('users', Mockery::any())
            ->andReturn(['value' => 150]);

        $this->analyticsService->shouldReceive('getRealTimeData')
            ->with('sessions', Mockery::any())
            ->andReturn(['value' => 30]);

        $results = $this->alertService->checkAllAlerts();

        $this->assertCount(2, $results); // Only active alerts
        $this->assertTrue($results[0]['triggered']); // Users > 100
        $this->assertTrue($results[1]['triggered']); // Sessions < 50
    }

    /** @test */
    public function it_respects_alert_cooldown_period()
    {
        $alert = Alert::factory()->create([
            'cooldown_period' => 300, // 5 minutes
            'last_triggered_at' => Carbon::now()->subMinutes(3) // 3 minutes ago
        ]);

        $this->analyticsService->shouldReceive('getRealTimeData')
            ->andReturn(['value' => 1500]);

        $result = $this->alertService->evaluateCondition($alert);

        $this->assertFalse($result['triggered']);
        $this->assertEquals('cooldown', $result['status']);
    }

    /** @test */
    public function it_can_snooze_alert()
    {
        $alert = Alert::factory()->create(['is_active' => true]);
        
        $snoozeUntil = Carbon::now()->addHours(2);
        $snoozedAlert = $this->alertService->snoozeAlert($alert, $snoozeUntil);

        $this->assertFalse($snoozedAlert->is_active);
        $this->assertEquals($snoozeUntil->toDateTimeString(), $snoozedAlert->snoozed_until->toDateTimeString());
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_acknowledge_alert()
    {
        $alert = Alert::factory()->create();
        
        $acknowledgedAlert = $this->alertService->acknowledgeAlert($alert, 'admin@example.com');

        $this->assertNotNull($acknowledgedAlert->acknowledged_at);
        $this->assertEquals('admin@example.com', $acknowledgedAlert->acknowledged_by);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'acknowledged_by' => 'admin@example.com'
        ]);
    }

    /** @test */
    public function it_can_get_alert_history()
    {
        $alert = Alert::factory()->create();
        
        // Create some alert history records
        for ($i = 0; $i < 5; $i++) {
            DB::table('alert_logs')->insert([
                'alert_id' => $alert->id,
                'triggered_at' => Carbon::now()->subHours($i),
                'status' => 'triggered',
                'data' => json_encode(['value' => 100 + $i]),
                'created_at' => Carbon::now()->subHours($i),
                'updated_at' => Carbon::now()->subHours($i)
            ]);
        }

        $history = $this->alertService->getAlertHistory($alert, 10);

        $this->assertCount(5, $history);
        $this->assertEquals('triggered', $history[0]['status']);
    }

    /** @test */
    public function it_can_get_alert_statistics()
    {
        // Create test alerts with different statuses
        Alert::factory()->count(3)->create(['is_active' => true]);
        Alert::factory()->count(2)->create(['is_active' => false]);
        Alert::factory()->create([
            'is_active' => true,
            'last_triggered_at' => Carbon::now()->subMinutes(10)
        ]);

        $statistics = $this->alertService->getAlertStatistics();

        $this->assertArrayHasKey('total_alerts', $statistics);
        $this->assertArrayHasKey('active_alerts', $statistics);
        $this->assertArrayHasKey('triggered_alerts', $statistics);
        $this->assertArrayHasKey('alerts_by_severity', $statistics);
        $this->assertArrayHasKey('alerts_by_type', $statistics);
        
        $this->assertEquals(6, $statistics['total_alerts']);
        $this->assertEquals(4, $statistics['active_alerts']);
    }

    /** @test */
    public function it_handles_notification_failures_gracefully()
    {
        $alert = Alert::factory()->create([
            'notification_channels' => ['webhook'],
            'notification_config' => [
                'webhook' => [
                    'url' => 'https://invalid-webhook.example.com',
                    'method' => 'POST'
                ]
            ]
        ]);

        Http::fake([
            'invalid-webhook.example.com/*' => Http::response('Server Error', 500)
        ]);

        $alertData = ['triggered' => true, 'current_value' => 1500];
        
        // Should not throw exception, but log the error
        $this->alertService->sendNotification($alert, $alertData);
        
        // Verify error was logged
        Log::shouldHaveReceived('error')
            ->with(Mockery::pattern('/Failed to send webhook notification/'));
    }

    /** @test */
    public function it_validates_alert_conditions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid threshold operator');

        $invalidAlertData = [
            'name' => 'Invalid Alert',
            'type' => 'threshold',
            'metric' => 'users',
            'conditions' => [
                'operator' => 'invalid_operator', // Invalid operator
                'value' => 100
            ]
        ];

        $this->alertService->createAlert($invalidAlertData);
    }

    /** @test */
    public function it_can_test_alert_condition()
    {
        $alert = Alert::factory()->create([
            'type' => 'threshold',
            'metric' => 'active_users',
            'conditions' => [
                'operator' => 'greater_than',
                'value' => 500
            ]
        ]);

        $this->analyticsService->shouldReceive('getRealTimeData')
            ->with('active_users', Mockery::any())
            ->andReturn(['value' => 750]);

        $testResult = $this->alertService->testAlert($alert);

        $this->assertArrayHasKey('would_trigger', $testResult);
        $this->assertArrayHasKey('current_value', $testResult);
        $this->assertArrayHasKey('evaluation_time', $testResult);
        $this->assertTrue($testResult['would_trigger']);
        $this->assertEquals(750, $testResult['current_value']);
    }

    /** @test */
    public function it_can_bulk_update_alert_status()
    {
        $alerts = Alert::factory()->count(3)->create(['is_active' => true]);
        $alertIds = $alerts->pluck('id')->toArray();

        $this->alertService->bulkUpdateAlertStatus($alertIds, false);

        foreach ($alertIds as $alertId) {
            $this->assertDatabaseHas('alerts', [
                'id' => $alertId,
                'is_active' => false
            ]);
        }
    }

    /** @test */
    public function it_can_duplicate_alert()
    {
        $originalAlert = Alert::factory()->create([
            'name' => 'Original Alert',
            'type' => 'threshold',
            'conditions' => ['operator' => 'greater_than', 'value' => 100]
        ]);

        $duplicatedAlert = $this->alertService->duplicateAlert($originalAlert);

        $this->assertNotEquals($originalAlert->id, $duplicatedAlert->id);
        $this->assertEquals('Original Alert (Copy)', $duplicatedAlert->name);
        $this->assertEquals('threshold', $duplicatedAlert->type);
        $this->assertEquals($originalAlert->conditions, $duplicatedAlert->conditions);
        $this->assertDatabaseHas('alerts', [
            'name' => 'Original Alert (Copy)'
        ]);
    }
}