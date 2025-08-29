<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'name', 'code', 'type', 'category', 'subject',
        'content', 'variables', 'media_urls', 'is_active', 'is_default',
        'settings', 'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'media_urls' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    // Métodos auxiliares
    public function renderContent(array $variables = []): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}