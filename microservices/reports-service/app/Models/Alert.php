<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Alert extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'type',
        'severity',
        'status',
        'conditions',
        'data_source',
        'notification_config',
        'recipients',
        'check_interval',
        'cooldown_period',
        'is_active',
        'last_checked_at',
        'last_triggered_at',
        'trigger_count',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'notification_config' => 'array',
        'recipients' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_triggered_at' => 'datetime',
        'check_interval' => 'integer',
        'cooldown_period' => 'integer',
        'trigger_count' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'severity', 'is_active', 'conditions'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs()
    {
        return $this->hasMany(AlertLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeTriggered($query)
    {
        return $query->where('status', 'triggered');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeDueForCheck($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('last_checked_at')
                          ->orWhereRaw('last_checked_at + INTERVAL check_interval SECOND <= NOW()');
                    });
    }

    public function scopeInCooldown($query)
    {
        return $query->whereNotNull('last_triggered_at')
                    ->whereRaw('last_triggered_at + INTERVAL cooldown_period SECOND > NOW()');
    }

    // Methods
    public function isDueForCheck()
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_checked_at) {
            return true;
        }

        return $this->last_checked_at->addSeconds($this->check_interval)->isPast();
    }

    public function isInCooldown()
    {
        if (!$this->last_triggered_at || !$this->cooldown_period) {
            return false;
        }

        return $this->last_triggered_at->addSeconds($this->cooldown_period)->isFuture();
    }

    public function canTrigger()
    {
        return $this->is_active && !$this->isInCooldown();
    }

    public function trigger($data = null)
    {
        if (!$this->canTrigger()) {
            return false;
        }

        $this->update([
            'status' => 'triggered',
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ]);

        // Log the trigger event
        $this->logs()->create([
            'event_type' => 'triggered',
            'severity' => $this->severity,
            'message' => 'Alert triggered',
            'trigger_data' => $data,
            'notification_sent' => false,
        ]);

        return true;
    }

    public function resolve($message = null)
    {
        $this->update([
            'status' => 'resolved',
        ]);

        // Log the resolve event
        $this->logs()->create([
            'event_type' => 'resolved',
            'severity' => 'info',
            'message' => $message ?? 'Alert resolved',
            'notification_sent' => false,
        ]);

        return true;
    }

    public function acknowledge($userId, $message = null)
    {
        $log = $this->logs()->where('event_type', 'triggered')
                           ->where('is_acknowledged', false)
                           ->latest()
                           ->first();

        if ($log) {
            $log->update([
                'is_acknowledged' => true,
                'acknowledged_by' => $userId,
                'acknowledged_at' => now(),
            ]);
        }

        // Create acknowledgment log
        $this->logs()->create([
            'event_type' => 'acknowledged',
            'severity' => 'info',
            'message' => $message ?? 'Alert acknowledged',
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);

        return true;
    }

    public function updateLastChecked()
    {
        $this->update(['last_checked_at' => now()]);
    }

    public function getDefaultConditions()
    {
        return [
            'operator' => 'greater_than',
            'value' => 0,
            'field' => 'count',
            'aggregation' => 'sum',
            'time_window' => 300, // 5 minutes
        ];
    }

    public function getDefaultNotificationConfig()
    {
        return [
            'email' => [
                'enabled' => true,
                'template' => 'alert-notification',
            ],
            'slack' => [
                'enabled' => false,
                'webhook_url' => null,
                'channel' => null,
            ],
            'webhook' => [
                'enabled' => false,
                'url' => null,
                'method' => 'POST',
                'headers' => [],
            ],
        ];
    }

    public function evaluateConditions($data)
    {
        $conditions = $this->conditions ?? $this->getDefaultConditions();
        
        // Extract the value to check based on field and aggregation
        $value = $this->extractValue($data, $conditions);
        
        // Evaluate the condition
        return $this->evaluateCondition($value, $conditions);
    }

    private function extractValue($data, $conditions)
    {
        $field = $conditions['field'] ?? 'count';
        $aggregation = $conditions['aggregation'] ?? 'sum';
        
        if (!isset($data[$field])) {
            return 0;
        }
        
        $values = is_array($data[$field]) ? $data[$field] : [$data[$field]];
        
        switch ($aggregation) {
            case 'sum':
                return array_sum($values);
            case 'avg':
                return count($values) > 0 ? array_sum($values) / count($values) : 0;
            case 'max':
                return count($values) > 0 ? max($values) : 0;
            case 'min':
                return count($values) > 0 ? min($values) : 0;
            case 'count':
                return count($values);
            default:
                return $values[0] ?? 0;
        }
    }

    private function evaluateCondition($value, $conditions)
    {
        $operator = $conditions['operator'] ?? 'greater_than';
        $threshold = $conditions['value'] ?? 0;
        
        switch ($operator) {
            case 'greater_than':
                return $value > $threshold;
            case 'greater_than_or_equal':
                return $value >= $threshold;
            case 'less_than':
                return $value < $threshold;
            case 'less_than_or_equal':
                return $value <= $threshold;
            case 'equal':
                return $value == $threshold;
            case 'not_equal':
                return $value != $threshold;
            default:
                return false;
        }
    }
}
