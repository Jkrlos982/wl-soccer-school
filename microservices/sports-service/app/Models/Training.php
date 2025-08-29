<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'category_id', 'date', 'start_time', 'end_time',
        'location', 'type', 'objectives', 'activities', 'observations',
        'status', 'coach_id', 'weather_conditions', 'duration_minutes'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'weather_conditions' => 'array'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCoach($query, $coachId)
    {
        return $query->where('coach_id', $coachId);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('date', 'desc')
                    ->orderBy('start_time', 'desc')
                    ->limit($limit);
    }

    // Métodos auxiliares
    public function getAttendanceStats()
    {
        $total = $this->category->players()->active()->count();
        $present = $this->attendances()->where('status', 'present')->count();
        $absent = $this->attendances()->where('status', 'absent')->count();
        $late = $this->attendances()->where('status', 'late')->count();

        return [
            'total_players' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
        ];
    }

    public function canTakeAttendance()
    {
        return $this->status === 'in_progress' || 
               ($this->status === 'scheduled' && $this->date->isToday());
    }

    public function isToday()
    {
        return $this->date->isToday();
    }

    /**
     * Reglas de validación para el modelo Training
     */
    public static function validationRules()
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:200',
            'type' => 'required|in:training,match,friendly,tournament',
            'objectives' => 'nullable|string|max:1000',
            'activities' => 'nullable|string|max:2000',
            'observations' => 'nullable|string|max:2000',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'coach_id' => 'required|exists:users,id',
            'weather_conditions' => 'nullable|array',
            'duration_minutes' => 'nullable|integer|min:1|max:300'
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public static function validationMessages()
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'location.required' => 'La ubicación es obligatoria.',
            'location.max' => 'La ubicación no puede exceder 200 caracteres.',
            'type.required' => 'El tipo de entrenamiento es obligatorio.',
            'type.in' => 'El tipo debe ser: entrenamiento, partido, amistoso o torneo.',
            'objectives.max' => 'Los objetivos no pueden exceder 1000 caracteres.',
            'activities.max' => 'Las actividades no pueden exceder 2000 caracteres.',
            'observations.max' => 'Las observaciones no pueden exceder 2000 caracteres.',
            'status.in' => 'El estado debe ser: programado, en progreso, completado o cancelado.',
            'coach_id.required' => 'El entrenador es obligatorio.',
            'coach_id.exists' => 'El entrenador seleccionado no existe.',
            'duration_minutes.integer' => 'La duración debe ser un número entero.',
            'duration_minutes.min' => 'La duración mínima es 1 minuto.',
            'duration_minutes.max' => 'La duración máxima es 300 minutos.'
        ];
    }
}