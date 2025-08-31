<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GeneratedReport extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'status',
        'format',
        'parameters',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'generated_at',
        'expires_at',
        'metadata',
        'error_message',
        'download_count',
        'last_downloaded_at',
        'generated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    protected $dates = [
        'generated_at',
        'expires_at',
        'last_downloaded_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'format', 'file_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    // Methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function getFileUrl()
    {
        if (!$this->file_path || !$this->isCompleted()) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getDownloadUrl()
    {
        if (!$this->file_path || !$this->isCompleted()) {
            return null;
        }

        return route('reports.download', $this->id);
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    public function getFileSizeFormatted()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function deleteFile()
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($report) {
            $report->deleteFile();
        });
    }
}
