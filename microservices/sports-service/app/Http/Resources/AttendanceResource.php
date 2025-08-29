<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'training_id' => $this->training_id,
            'player_id' => $this->player_id,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'arrival_time' => $this->arrival_time?->format('H:i'),
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'recorded_at' => $this->recorded_at?->format('Y-m-d H:i:s'),
            
            // Información adicional
            'is_late' => $this->isLate(),
            'late_duration_minutes' => $this->getLateDuration(),
            
            // Relaciones
            'training' => $this->whenLoaded('training', function () {
                return [
                    'id' => $this->training->id,
                    'date' => $this->training->date?->format('Y-m-d'),
                    'start_time' => $this->training->start_time?->format('H:i'),
                    'end_time' => $this->training->end_time?->format('H:i'),
                    'location' => $this->training->location,
                    'type' => $this->training->type,
                    'category' => $this->whenLoaded('training.category', function () {
                        return [
                            'id' => $this->training->category->id,
                            'name' => $this->training->category->name,
                            'age_group' => $this->training->category->age_group
                        ];
                    })
                ];
            }),
            
            'player' => $this->whenLoaded('player', function () {
                return [
                    'id' => $this->player->id,
                    'first_name' => $this->player->first_name,
                    'last_name' => $this->player->last_name,
                    'full_name' => $this->player->first_name . ' ' . $this->player->last_name,
                    'jersey_number' => $this->player->jersey_number,
                    'position' => $this->player->position
                ];
            }),
            
            'recorded_by_user' => $this->whenLoaded('recordedBy', function () {
                return [
                    'id' => $this->recordedBy->id,
                    'name' => $this->recordedBy->name,
                    'email' => $this->recordedBy->email
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Enlaces útiles
            'links' => [
                'self' => route('attendances.show', $this->id),
                'update' => route('attendances.update', $this->id),
                'training' => route('trainings.show', $this->training_id),
                'player' => route('players.show', $this->player_id)
            ]
        ];
    }
    
    /**
     * Obtener la etiqueta del estado en español
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'present' => 'Presente',
            'absent' => 'Ausente',
            'late' => 'Tarde',
            'excused' => 'Justificado',
            default => 'Desconocido'
        };
    }
}