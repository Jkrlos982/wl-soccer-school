<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'format',
        'parameters',
        'data_sources',
        'template_content',
        'styling',
        'charts_config',
        'frequency',
        'schedule_config',
        'is_active',
        'is_public',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data_sources' => 'array',
        'styling' => 'array',
        'charts_config' => 'array',
        'schedule_config' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'format', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get all generated reports for this template.
     */
    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class, 'template_id');
    }

    /**
     * Get active generated reports.
     */
    public function activeReports(): HasMany
    {
        return $this->generatedReports()->whereIn('status', ['completed', 'processing']);
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public templates.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for templates by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for scheduled templates.
     */
    public function scopeScheduled($query)
    {
        return $query->where('frequency', '!=', 'manual');
    }

    /**
     * Check if template is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->frequency !== 'manual';
    }

    /**
     * Get template parameters with defaults.
     */
    public function getParametersWithDefaults(): array
    {
        $parameters = $this->parameters ?? [];
        $defaults = [];
        
        foreach ($parameters as $param) {
            if (isset($param['default'])) {
                $defaults[$param['name']] = $param['default'];
            }
        }
        
        return $defaults;
    }
}
