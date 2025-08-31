<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Exception;

class AlertService
{
    protected $analyticsService;
    protected $dashboardService;

    public function __construct(AnalyticsService $analyticsService, DashboardService $dashboardService)
    {
        $this->analyticsService = $analyticsService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Check all active alerts
     */
    public function checkAllAlerts()
    {
        $alerts = Alert::active()->dueForCheck()->get();
        $results = [];

        foreach ($alerts as $alert) {
            try {
                $result = $this->checkAlert($alert);
                $results[] = $result;
            } catch (Exception $e) {
                Log::error('Alert check failed', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Check a specific alert
     */
    public function checkAlert(Alert $alert)
    {
        // Update last checked timestamp
        $alert->updateLastChecked();

        // Skip if alert is in cooldown period
        if ($alert->isInCooldown()) {
            return [
                'alert_id' => $alert->id,
                'status' => 'skipped',
                'reason' => 'cooldown_period'
            ];
        }

        // Evaluate alert conditions
        $conditionsMet = $this->evaluateConditions($alert);

        if ($conditionsMet['triggered']) {
            return $this->handleAlertTriggered($alert, $conditionsMet);
        } else {
            return $this->handleAlertResolved($alert, $conditionsMet);
        }
    }

    /**
     * Evaluate alert conditions
     */
    protected function evaluateConditions(Alert $alert)
    {
        $conditions = $alert->conditions;
        $results = [
            'triggered' => false,
            'conditions_met' => [],
            'trigger_data' => []
        ];

        foreach ($conditions as $condition) {
            $conditionResult = $this->evaluateCondition($condition, $alert);
            $results['conditions_met'][] = $conditionResult;
            $results['trigger_data'][$condition['name']] = $conditionResult['value'];

            // For AND logic, all conditions must be met
            if ($condition['logic'] === 'AND' && !$conditionResult['met']) {
                $results['triggered'] = false;
                break;
            }
            // For OR logic, any condition can trigger
            if ($condition['logic'] === 'OR' && $conditionResult['met']) {
                $results['triggered'] = true;
            }
        }

        // If no OR conditions were met and we have AND conditions
        if (!$results['triggered'] && $this->hasAndConditions($conditions)) {
            $results['triggered'] = $this->allAndConditionsMet($results['conditions_met']);
        }

        return $results;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, Alert $alert)
    {
        $value = $this->fetchConditionValue($condition, $alert);
        $threshold = $condition['threshold'];
        $operator = $condition['operator'];

        $met = $this->compareValues($value, $operator, $threshold);

        return [
            'name' => $condition['name'],
            'value' => $value,
            'threshold' => $threshold,
            'operator' => $operator,
            'met' => $met
        ];
    }

    /**
     * Fetch value for condition evaluation
     */
    protected function fetchConditionValue(array $condition, Alert $alert)
    {
        switch ($condition['source_type']) {
            case 'database':
                return $this->fetchDatabaseValue($condition);
            case 'analytics':
                return $this->fetchAnalyticsValue($condition);
            case 'widget':
                return $this->fetchWidgetValue($condition);
            case 'api':
                return $this->fetchApiValue($condition);
            default:
                throw new Exception("Unsupported condition source type: {$condition['source_type']}");
        }
    }

    /**
     * Fetch value from database
     */
    protected function fetchDatabaseValue(array $condition)
    {
        $query = DB::table($condition['table']);

        // Apply filters
        if (isset($condition['filters'])) {
            foreach ($condition['filters'] as $filter) {
                $query->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }

        // Apply date range if specified
        if (isset($condition['date_range'])) {
            $dateField = $condition['date_field'] ?? 'created_at';
            $range = $this->calculateDateRange($condition['date_range']);
            $query->whereBetween($dateField, [$range['start'], $range['end']]);
        }

        // Apply aggregation
        switch ($condition['aggregation']) {
            case 'count':
                return $query->count();
            case 'sum':
                return $query->sum($condition['field']);
            case 'avg':
                return $query->avg($condition['field']);
            case 'max':
                return $query->max($condition['field']);
            case 'min':
                return $query->min($condition['field']);
            default:
                return $query->count();
        }
    }

    /**
     * Fetch value from analytics service
     */
    protected function fetchAnalyticsValue(array $condition)
    {
        $data = $this->analyticsService->getAnalyticsData($condition['source'], $condition['parameters'] ?? []);
        return $data[$condition['metric']] ?? 0;
    }

    /**
     * Fetch value from widget
     */
    protected function fetchWidgetValue(array $condition)
    {
        $data = $this->dashboardService->getWidgetData($condition['source'], $condition['parameters'] ?? []);
        return $data['value'] ?? 0;
    }

    /**
     * Fetch value from external API
     */
    protected function fetchApiValue(array $condition)
    {
        // Implementation for API calls
        // This would include HTTP client calls to external services
        return 0;
    }

    /**
     * Compare values based on operator
     */
    protected function compareValues($value, $operator, $threshold)
    {
        switch ($operator) {
            case '>':
                return $value > $threshold;
            case '>=':
                return $value >= $threshold;
            case '<':
                return $value < $threshold;
            case '<=':
                return $value <= $threshold;
            case '=':
            case '==':
                return $value == $threshold;
            case '!=':
                return $value != $threshold;
            case 'between':
                return $value >= $threshold[0] && $value <= $threshold[1];
            case 'not_between':
                return $value < $threshold[0] || $value > $threshold[1];
            default:
                return false;
        }
    }

    /**
     * Handle alert triggered
     */
    protected function handleAlertTriggered(Alert $alert, array $conditionResults)
    {
        // Check if alert is already triggered to avoid spam
        if ($alert->status === 'triggered') {
            return [
                'alert_id' => $alert->id,
                'status' => 'already_triggered',
                'conditions' => $conditionResults
            ];
        }

        // Trigger the alert
        $alert->trigger($conditionResults['trigger_data']);

        // Log the event
        $this->logAlertEvent($alert, 'triggered', $conditionResults);

        // Send notifications
        $this->sendNotifications($alert, 'triggered', $conditionResults);

        return [
            'alert_id' => $alert->id,
            'status' => 'triggered',
            'conditions' => $conditionResults,
            'notifications_sent' => true
        ];
    }

    /**
     * Handle alert resolved
     */
    protected function handleAlertResolved(Alert $alert, array $conditionResults)
    {
        // Only resolve if alert was previously triggered
        if ($alert->status !== 'triggered') {
            return [
                'alert_id' => $alert->id,
                'status' => 'not_triggered',
                'conditions' => $conditionResults
            ];
        }

        // Resolve the alert
        $alert->resolve();

        // Log the event
        $this->logAlertEvent($alert, 'resolved', $conditionResults);

        // Send resolution notifications if configured
        if ($alert->notification_config['send_resolution'] ?? false) {
            $this->sendNotifications($alert, 'resolved', $conditionResults);
        }

        return [
            'alert_id' => $alert->id,
            'status' => 'resolved',
            'conditions' => $conditionResults,
            'notifications_sent' => $alert->notification_config['send_resolution'] ?? false
        ];
    }

    /**
     * Log alert event
     */
    protected function logAlertEvent(Alert $alert, string $eventType, array $conditionResults)
    {
        AlertLog::create([
            'alert_id' => $alert->id,
            'event_type' => $eventType,
            'severity' => $alert->severity,
            'message' => $this->buildAlertMessage($alert, $eventType, $conditionResults),
            'trigger_data' => $conditionResults['trigger_data'] ?? [],
            'notification_sent' => false,
            'metadata' => [
                'conditions_met' => $conditionResults['conditions_met'] ?? [],
                'check_time' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Send notifications for alert
     */
    protected function sendNotifications(Alert $alert, string $eventType, array $conditionResults)
    {
        $notificationConfig = $alert->notification_config;
        $recipients = $alert->recipients;

        foreach ($notificationConfig['channels'] ?? [] as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($alert, $eventType, $conditionResults, $recipients);
                        break;
                    case 'slack':
                        $this->sendSlackNotification($alert, $eventType, $conditionResults, $recipients);
                        break;
                    case 'webhook':
                        $this->sendWebhookNotification($alert, $eventType, $conditionResults, $recipients);
                        break;
                    case 'sms':
                        $this->sendSmsNotification($alert, $eventType, $conditionResults, $recipients);
                        break;
                }
            } catch (Exception $e) {
                Log::error('Notification sending failed', [
                    'alert_id' => $alert->id,
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Mark notifications as sent in the latest log entry
        $latestLog = AlertLog::where('alert_id', $alert->id)
            ->where('event_type', $eventType)
            ->latest()
            ->first();

        if ($latestLog) {
            $latestLog->markNotificationSent();
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(Alert $alert, string $eventType, array $conditionResults, array $recipients)
    {
        // Implementation for email notifications
        // This would use Laravel's Mail facade to send emails
    }

    /**
     * Send Slack notification
     */
    protected function sendSlackNotification(Alert $alert, string $eventType, array $conditionResults, array $recipients)
    {
        // Implementation for Slack notifications
        // This would use Slack API or webhooks
    }

    /**
     * Send webhook notification
     */
    protected function sendWebhookNotification(Alert $alert, string $eventType, array $conditionResults, array $recipients)
    {
        // Implementation for webhook notifications
        // This would make HTTP POST requests to configured URLs
    }

    /**
     * Send SMS notification
     */
    protected function sendSmsNotification(Alert $alert, string $eventType, array $conditionResults, array $recipients)
    {
        // Implementation for SMS notifications
        // This would use services like Twilio or AWS SNS
    }

    /**
     * Build alert message
     */
    protected function buildAlertMessage(Alert $alert, string $eventType, array $conditionResults)
    {
        $message = "Alert '{$alert->name}' has been {$eventType}.";
        
        if ($eventType === 'triggered') {
            $message .= " Conditions met: ";
            foreach ($conditionResults['conditions_met'] ?? [] as $condition) {
                if ($condition['met']) {
                    $message .= "{$condition['name']}: {$condition['value']} {$condition['operator']} {$condition['threshold']}; ";
                }
            }
        }
        
        return $message;
    }

    /**
     * Calculate date range for conditions
     */
    protected function calculateDateRange(string $range)
    {
        $now = now();
        
        switch ($range) {
            case 'last_hour':
                return ['start' => $now->subHour(), 'end' => $now];
            case 'last_24_hours':
                return ['start' => $now->subDay(), 'end' => $now];
            case 'last_7_days':
                return ['start' => $now->subWeek(), 'end' => $now];
            case 'last_30_days':
                return ['start' => $now->subMonth(), 'end' => $now];
            case 'current_month':
                return ['start' => $now->startOfMonth(), 'end' => $now->endOfMonth()];
            case 'current_week':
                return ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];
            default:
                return ['start' => $now->subDay(), 'end' => $now];
        }
    }

    /**
     * Check if conditions array has AND conditions
     */
    protected function hasAndConditions(array $conditions)
    {
        foreach ($conditions as $condition) {
            if (($condition['logic'] ?? 'AND') === 'AND') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all AND conditions are met
     */
    protected function allAndConditionsMet(array $conditionResults)
    {
        foreach ($conditionResults as $result) {
            if (!$result['met']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create new alert
     */
    public function createAlert(array $data)
    {
        return Alert::create($data);
    }

    /**
     * Update alert
     */
    public function updateAlert(Alert $alert, array $data)
    {
        return $alert->update($data);
    }

    /**
     * Delete alert
     */
    public function deleteAlert(Alert $alert)
    {
        return $alert->delete();
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(Alert $alert, $userId, string $message = null)
    {
        $alert->acknowledge($userId);
        
        // Log acknowledgment
        AlertLog::create([
            'alert_id' => $alert->id,
            'event_type' => 'acknowledged',
            'severity' => $alert->severity,
            'message' => $message ?? "Alert acknowledged by user {$userId}",
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'metadata' => [
                'acknowledgment_message' => $message
            ]
        ]);
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics(array $filters = [])
    {
        $query = Alert::query();
        
        // Apply filters
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        }
        
        return [
            'total_alerts' => $query->count(),
            'active_alerts' => $query->where('is_active', true)->count(),
            'triggered_alerts' => $query->where('status', 'triggered')->count(),
            'resolved_alerts' => $query->where('status', 'resolved')->count(),
            'by_severity' => $query->groupBy('severity')
                ->selectRaw('severity, count(*) as count')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_type' => $query->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray()
        ];
    }

    /**
     * Test alert conditions without triggering
     */
    public function testAlert(Alert $alert)
    {
        return $this->evaluateConditions($alert);
    }
}