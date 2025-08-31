<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DashboardLayout extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'layout_config',
        'widget_positions',
        'theme_settings',
        'is_default',
        'is_public',
        'permissions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'widget_positions' => 'array',
        'theme_settings' => 'array',
        'permissions' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_default', 'is_public', 'layout_config'])
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

    public function widgets()
    {
        return $this->belongsToMany(DashboardWidget::class, 'dashboard_layout_widgets')
                    ->withPivot(['position', 'size', 'configuration'])
                    ->withTimestamps();
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('created_by', $userId)
              ->orWhereJsonContains('permissions->users', $userId);
        });
    }

    // Methods
    public function getDefaultLayoutConfig()
    {
        $defaults = [
            'columns' => 12,
            'rowHeight' => 60,
            'margin' => [10, 10],
            'containerPadding' => [10, 10],
            'compactType' => 'vertical',
            'preventCollision' => false,
            'isDraggable' => true,
            'isResizable' => true,
        ];

        return array_merge($defaults, $this->layout_config ?? []);
    }

    public function getDefaultThemeSettings()
    {
        $defaults = [
            'primaryColor' => '#3B82F6',
            'secondaryColor' => '#10B981',
            'backgroundColor' => '#F9FAFB',
            'cardBackground' => '#FFFFFF',
            'textColor' => '#111827',
            'borderColor' => '#E5E7EB',
            'fontFamily' => 'Inter, sans-serif',
            'borderRadius' => '8px',
        ];

        return array_merge($defaults, $this->theme_settings ?? []);
    }

    public function addWidget($widgetId, $position = null, $size = null, $configuration = null)
    {
        $defaultPosition = [
            'x' => 0,
            'y' => 0,
            'w' => 4,
            'h' => 3,
        ];

        $defaultSize = [
            'minW' => 2,
            'minH' => 2,
            'maxW' => 12,
            'maxH' => 10,
        ];

        return $this->widgets()->attach($widgetId, [
            'position' => json_encode(array_merge($defaultPosition, $position ?? [])),
            'size' => json_encode(array_merge($defaultSize, $size ?? [])),
            'configuration' => json_encode($configuration ?? []),
        ]);
    }

    public function removeWidget($widgetId)
    {
        return $this->widgets()->detach($widgetId);
    }

    public function updateWidgetPosition($widgetId, $position)
    {
        return $this->widgets()->updateExistingPivot($widgetId, [
            'position' => json_encode($position),
        ]);
    }

    public function updateWidgetConfiguration($widgetId, $configuration)
    {
        return $this->widgets()->updateExistingPivot($widgetId, [
            'configuration' => json_encode($configuration),
        ]);
    }

    public function canBeAccessedBy($user)
    {
        if ($this->is_public) {
            return true;
        }

        if ($user->id === $this->created_by) {
            return true;
        }

        if (!$this->permissions) {
            return false;
        }

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
        $layout = $this->replicate();
        $layout->name = $newName ?? ($this->name . ' (Copy)');
        $layout->slug = null; // Will be auto-generated
        $layout->is_default = false;
        $layout->created_by = auth()->id();
        $layout->save();

        // Copy widget relationships
        foreach ($this->widgets as $widget) {
            $layout->widgets()->attach($widget->id, [
                'position' => $widget->pivot->position,
                'size' => $widget->pivot->size,
                'configuration' => $widget->pivot->configuration,
            ]);
        }

        return $layout;
    }

    public function setAsDefault()
    {
        // Remove default from other layouts
        static::where('is_default', true)->update(['is_default' => false]);
        
        // Set this layout as default
        $this->update(['is_default' => true]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($layout) {
            if (!$layout->slug) {
                $layout->slug = Str::slug($layout->name);
            }
        });

        static::updating(function ($layout) {
            if ($layout->isDirty('name') && !$layout->isDirty('slug')) {
                $layout->slug = Str::slug($layout->name);
            }
        });
    }
}
