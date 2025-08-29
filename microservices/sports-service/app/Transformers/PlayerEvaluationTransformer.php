<?php

namespace App\Transformers;

use App\Models\PlayerEvaluation;
use League\Fractal\TransformerAbstract;

class PlayerEvaluationTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     */
    protected array $availableIncludes = [
        'player',
        'evaluator',
        'training',
        'school'
    ];

    /**
     * Transform the PlayerEvaluation model into an array.
     */
    public function transform(PlayerEvaluation $evaluation): array
    {
        return [
            'id' => $evaluation->id,
            'player_id' => $evaluation->player_id,
            'evaluator_id' => $evaluation->evaluator_id,
            'training_id' => $evaluation->training_id,
            'evaluation_date' => $evaluation->evaluation_date?->format('Y-m-d'),
            'evaluation_type' => $evaluation->evaluation_type,
            'evaluation_type_display' => $this->getEvaluationTypeDisplay($evaluation->evaluation_type),
            
            // Technical Skills
            'technical_skills' => $evaluation->technical_skills,
            'ball_control' => $evaluation->ball_control,
            'passing' => $evaluation->passing,
            'shooting' => $evaluation->shooting,
            'dribbling' => $evaluation->dribbling,
            'technical_average' => $evaluation->getTechnicalAverage(),
            
            // Physical Skills
            'speed' => $evaluation->speed,
            'endurance' => $evaluation->endurance,
            'strength' => $evaluation->strength,
            'agility' => $evaluation->agility,
            'physical_average' => $evaluation->getPhysicalAverage(),
            
            // Tactical Skills
            'positioning' => $evaluation->positioning,
            'decision_making' => $evaluation->decision_making,
            'teamwork' => $evaluation->teamwork,
            'game_understanding' => $evaluation->game_understanding,
            'tactical_average' => $evaluation->getTacticalAverage(),
            
            // Mental/Attitudinal Skills
            'attitude' => $evaluation->attitude,
            'discipline' => $evaluation->discipline,
            'leadership' => $evaluation->leadership,
            'commitment' => $evaluation->commitment,
            'mental_average' => $evaluation->getMentalAverage(),
            
            // Overall
            'overall_rating' => $evaluation->overall_rating,
            'calculated_overall' => $evaluation->getOverallAverage(),
            
            // Comments and Goals
            'strengths' => $evaluation->strengths,
            'areas_for_improvement' => $evaluation->areas_for_improvement,
            'general_comments' => $evaluation->general_comments,
            'short_term_goals' => $evaluation->short_term_goals,
            'long_term_goals' => $evaluation->long_term_goals,
            
            // Metadata
            'created_at' => $evaluation->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $evaluation->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Include Player
     */
    public function includePlayer(PlayerEvaluation $evaluation)
    {
        if ($evaluation->player) {
            return $this->item($evaluation->player, new PlayerTransformer());
        }
        return null;
    }

    /**
     * Include Evaluator (User)
     */
    public function includeEvaluator(PlayerEvaluation $evaluation)
    {
        if ($evaluation->evaluator) {
            return $this->item($evaluation->evaluator, function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });
        }
        return null;
    }

    /**
     * Include Training
     */
    public function includeTraining(PlayerEvaluation $evaluation)
    {
        if ($evaluation->training) {
            return $this->item($evaluation->training, function ($training) {
                return [
                    'id' => $training->id,
                    'title' => $training->title,
                    'date' => $training->date?->format('Y-m-d'),
                    'start_time' => $training->start_time,
                    'end_time' => $training->end_time,
                ];
            });
        }
        return null;
    }

    /**
     * Include School
     */
    public function includeSchool(PlayerEvaluation $evaluation)
    {
        if ($evaluation->school) {
            return $this->item($evaluation->school, function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                ];
            });
        }
        return null;
    }

    /**
     * Get evaluation type display name
     */
    private function getEvaluationTypeDisplay(string $type): string
    {
        return match($type) {
            'training' => 'Entrenamiento',
            'match' => 'Partido',
            'test' => 'Prueba',
            'periodic' => 'Periódica',
            default => ucfirst($type)
        };
    }
}