<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            
            // Información básica del entrenamiento
            'date' => $this->date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location' => $this->location,
            'type' => $this->type,
            'status' => $this->status,
            
            // Contenido del entrenamiento
            'objectives' => $this->objectives,
            'activities' => $this->activities,
            'observations' => $this->observations,
            
            // Información adicional
            'duration_minutes' => $this->duration_minutes,
            'weather_conditions' => $this->weather_conditions,
            
            // Relaciones
            'category' => new CategoryResource($this->whenLoaded('category')),
            'school' => [
                'id' => $this->school_id,
                'name' => $this->whenLoaded('school', fn() => $this->school->name)
            ],
            'coach' => [
                'id' => $this->coach_id,
                'name' => $this->whenLoaded('coach', fn() => $this->coach->name),
                'email' => $this->whenLoaded('coach', fn() => $this->coach->email)
            ],
            
            // Estadísticas de asistencia (si están cargadas)
            'attendance_stats' => $this->when(
                $this->relationLoaded('attendances'),
                fn() => [
                    'total_players' => $this->attendances->count(),
                    'present' => $this->attendances->where('status', 'present')->count(),
                    'absent' => $this->attendances->where('status', 'absent')->count(),
                    'late' => $this->attendances->where('status', 'late')->count(),
                    'excused' => $this->attendances->where('status', 'excused')->count(),
                    'attendance_rate' => $this->attendances->count() > 0 
                        ? round(($this->attendances->whereIn('status', ['present', 'late'])->count() / $this->attendances->count()) * 100, 2)
                        : 0
                ]
            ),
            
            // Asistencias detalladas (si están cargadas)
            'attendances' => $this->when(
                $this->relationLoaded('attendances') && $request->input('include_attendances'),
                fn() => $this->attendances->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'player_id' => $attendance->player_id,
                        'player_name' => $attendance->player->full_name ?? null,
                        'status' => $attendance->status,
                        'arrival_time' => $attendance->arrival_time,
                        'notes' => $attendance->notes
                    ];
                })
            ),
            
            // Información de estado
            'is_today' => $this->isToday(),
            'is_upcoming' => $this->date->isFuture(),
            'is_completed' => $this->status === 'completed',
            'can_start' => $this->status === 'scheduled' && $this->date->isToday(),
            'can_complete' => $this->status === 'in_progress',
            'can_cancel' => in_array($this->status, ['scheduled', 'in_progress']),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Enlaces útiles
            'links' => [
                'self' => route('api.trainings.show', $this->id),
                'edit' => route('api.trainings.show', $this->id),
                'delete' => route('api.trainings.show', $this->id),
                'start' => $this->when(
                    $this->status === 'scheduled' && $this->date->isToday(),
                    fn() => route('api.trainings.start', $this->id)
                ),
                'complete' => $this->when(
                    $this->status === 'in_progress',
                    fn() => route('api.trainings.complete', $this->id)
                ),
                'cancel' => $this->when(
                    in_array($this->status, ['scheduled', 'in_progress']),
                    fn() => route('api.trainings.cancel', $this->id)
                ),
                'category' => route('api.categories.show', $this->category_id),
                'attendances' => route('api.trainings.show', $this->id) . '?include_attendances=1'
            ]
        ];
    }
}