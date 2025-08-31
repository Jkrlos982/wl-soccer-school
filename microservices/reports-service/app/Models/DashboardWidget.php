<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DashboardWidget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'chart_type',
        'data_source',
        'configuration',
        'styling',
        'filters',
        'refresh_interval',
        'cache_duration',
        'is_real_time',
        'is_active',
        'is_public',
        'sort_order',
        'grid_position',
        'permissions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'configuration' => 'array',
        'styling' => 'array',
        'filters' => 'array',
        'grid_position' => 'array',
        'permissions' => 'array',
        'is_real_time' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'refresh_interval' => 'integer',
        'cache_duration' => 'integer',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'is_active', 'configuration'])
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

    public function layouts()
    {
        return $this->belongsToMany(DashboardLayout::class, 'dashboard_layout_widgets')
                    ->withPivot(['position', 'size', 'configuration'])
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeRealTime($query)
    {
        return $query->where('is_real_time', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChartType($query, $chartType)
    {
        return $query->where('chart_type', $chartType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Methods
    public function getCacheKey($params = [])
    {
        $key = "widget_data_{$this->id}";
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        return $key;
    }

    public function getCachedData($params = [])
    {
        if (!$this->cache_duration) {
            return null;
        }

        return Cache::get($this->getCacheKey($params));
    }

    public function setCachedData($data, $params = [])
    {
        if (!$this->cache_duration) {
            return false;
        }

        return Cache::put(
            $this->getCacheKey($params),
            $data,
            now()->addSeconds($this->cache_duration)
        );
    }

    public function clearCache($params = [])
    {
        return Cache::forget($this->getCacheKey($params));
    }

    public function shouldRefresh()
    {
        if ($this->is_real_time) {
            return true;
        }

        if (!$this->refresh_interval) {
            return false;
        }

        $lastUpdate = $this->updated_at;
        return $lastUpdate->addSeconds($this->refresh_interval)->isPast();
    }

    public function getDefaultConfiguration()
    {
        $defaults = [
            'chart' => [
                'width' => '100%',
                'height' => 300,
                'responsive' => true,
            ],
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#10B981',
                'accent' => '#F59E0B',
            ],
            'animation' => [
                'enabled' => true,
                'duration' => 1000,
            ],
        ];

        return array_merge($defaults, $this->configuration ?? []);
    }

    public function getDefaultStyling()
    {
        $defaults = [
            'background' => '#FFFFFF',
            'border' => '1px solid #E5E7EB',
            'borderRadius' => '8px',
            'padding' => '16px',
            'shadow' => '0 1px 3px rgba(0, 0, 0, 0.1)',
        ];

        return array_merge($defaults, $this->styling ?? []);
    }

    public function getGridPosition()
    {
        return array_merge([
            'x' => 0,
            'y' => 0,
            'w' => 4,
            'h' => 3,
        ], $this->grid_position ?? []);
    }

    public function canBeAccessedBy($user)
    {
        if ($this->is_public) {
            return true;
        }

        if (!$this->permissions) {
            return $user && $user->id === $this->created_by;
        }

        // Check permissions array
        $permissions = $this->permissions;
        
        if (isset($permissions['users']) && in_array($user->id, $permissions['users'])) {
            return true;
        }

        if (isset($permissions['roles']) && $user->hasAnyRole($permissions['roles'])) {
            return true;
        }

        return false;
    }

    public function duplicate($newName = null)
    {
        $widget = $this->replicate();
        $widget->name = $newName ?? ($this->name . ' (Copy)');
        $widget->slug = null; // Will be auto-generated
        $widget->created_by = auth()->id();
        $widget->save();

        return $widget;
    }
}
