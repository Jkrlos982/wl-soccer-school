<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerEvaluationResource extends JsonResource
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
            'player_id' => $this->player_id,
            'evaluator_id' => $this->evaluator_id,
            'training_id' => $this->training_id,
            'evaluation_date' => $this->evaluation_date?->format('Y-m-d'),
            'evaluation_type' => $this->evaluation_type,
            
            // Habilidades técnicas
            'technical_skills' => $this->technical_skills,
            'ball_control' => $this->ball_control,
            'passing' => $this->passing,
            'shooting' => $this->shooting,
            'dribbling' => $this->dribbling,
            
            // Habilidades físicas
            'speed' => $this->speed,
            'endurance' => $this->endurance,
            'strength' => $this->strength,
            'agility' => $this->agility,
            
            // Habilidades tácticas
            'positioning' => $this->positioning,
            'decision_making' => $this->decision_making,
            'teamwork' => $this->teamwork,
            'game_understanding' => $this->game_understanding,
            
            // Habilidades mentales/actitudinales
            'attitude' => $this->attitude,
            'discipline' => $this->discipline,
            'leadership' => $this->leadership,
            'commitment' => $this->commitment,
            
            // Evaluación general
            'overall_rating' => $this->overall_rating,
            'strengths' => $this->strengths,
            'areas_for_improvement' => $this->areas_for_improvement,
            'goals_next_period' => $this->goals_next_period,
            'coach_comments' => $this->coach_comments,
            'custom_metrics' => $this->custom_metrics,
            
            // Promedios calculados
            'technical_average' => $this->getTechnicalAverage(),
            'physical_average' => $this->getPhysicalAverage(),
            'tactical_average' => $this->getTacticalAverage(),
            'mental_average' => $this->getMentalAverage(),
            
            // Relaciones
            'player' => new PlayerResource($this->whenLoaded('player')),
            'evaluator' => new UserResource($this->whenLoaded('evaluator')),
            'training' => new TrainingResource($this->whenLoaded('training')),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}