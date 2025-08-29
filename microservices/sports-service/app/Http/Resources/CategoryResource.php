<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'age_range' => $this->min_age . '-' . $this->max_age . ' años',
            'gender' => $this->gender,
            'gender_display' => $this->getGenderDisplay(),
            'max_players' => $this->max_players,
            'training_days' => $this->training_days,
            'training_days_display' => $this->getTrainingDaysDisplay(),
            'training_start_time' => $this->training_start_time,
            'training_end_time' => $this->training_end_time,
            'training_schedule' => $this->training_start_time . ' - ' . $this->training_end_time,
            'field_location' => $this->field_location,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'Activa' : 'Inactiva',
            'coach_id' => $this->coach_id,
            'school_id' => $this->school_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relaciones condicionales
            'coach' => $this->whenLoaded('coach', function () {
                return [
                    'id' => $this->coach->id,
                    'name' => $this->coach->name,
                    'email' => $this->coach->email
                ];
            }),
            
            // Enlaces de acciones
            'links' => [
                'self' => route('api.v1.categories.show', $this->id),
                'edit' => route('api.v1.categories.update', $this->id),
                'delete' => route('api.v1.categories.destroy', $this->id)
            ]
        ];
    }
    
    /**
     * Get gender display name
     */
    private function getGenderDisplay(): string
    {
        return match($this->gender) {
            'male' => 'Masculino',
            'female' => 'Femenino',
            'mixed' => 'Mixto',
            default => 'No especificado'
        };
    }
    
    /**
     * Get training days display
     */
    private function getTrainingDaysDisplay(): string
    {
        if (!$this->training_days || !is_array($this->training_days)) {
            return 'No definido';
        }
        
        $daysMap = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo'
        ];
        
        $displayDays = array_map(function($day) use ($daysMap) {
            return $daysMap[$day] ?? $day;
        }, $this->training_days);
        
        return implode(', ', $displayDays);
    }
}