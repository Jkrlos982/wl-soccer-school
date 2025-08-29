<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerStatisticResource extends JsonResource
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
            'training_id' => $this->training_id,
            'match_id' => $this->match_id,
            'date' => $this->date?->format('Y-m-d'),
            'context' => $this->context,
            
            // Training Statistics
            'training_stats' => [
                'minutes_played' => $this->minutes_played,
                'goals_scored' => $this->goals_scored,
                'assists' => $this->assists,
                'passes_completed' => $this->passes_completed,
                'passes_attempted' => $this->passes_attempted,
                'pass_accuracy' => $this->pass_accuracy,
                'shots_on_target' => $this->shots_on_target,
                'shots_attempted' => $this->shots_attempted,
                'shot_accuracy' => $this->shot_accuracy,
                'tackles_won' => $this->tackles_won,
                'tackles_attempted' => $this->tackles_attempted,
                'interceptions' => $this->interceptions,
                'fouls_committed' => $this->fouls_committed,
                'fouls_received' => $this->fouls_received,
                'yellow_cards' => $this->yellow_cards,
                'red_cards' => $this->red_cards,
                'distance_covered' => $this->distance_covered,
                'sprint_count' => $this->sprint_count,
                'max_speed' => $this->max_speed,
                'average_heart_rate' => $this->average_heart_rate,
                'max_heart_rate' => $this->max_heart_rate,
            ],
            
            // Position-specific Statistics
            'goalkeeper_stats' => $this->when($this->context === 'match' || $this->saves !== null, [
                'saves' => $this->saves,
                'goals_conceded' => $this->goals_conceded,
                'clean_sheets' => $this->clean_sheets,
                'penalties_saved' => $this->penalties_saved,
                'distribution_accuracy' => $this->distribution_accuracy,
            ]),
            
            'defender_stats' => $this->when($this->clearances !== null, [
                'clearances' => $this->clearances,
                'blocks' => $this->blocks,
                'aerial_duels_won' => $this->aerial_duels_won,
                'aerial_duels_attempted' => $this->aerial_duels_attempted,
            ]),
            
            'midfielder_stats' => $this->when($this->key_passes !== null, [
                'key_passes' => $this->key_passes,
                'crosses_completed' => $this->crosses_completed,
                'crosses_attempted' => $this->crosses_attempted,
                'dribbles_successful' => $this->dribbles_successful,
                'dribbles_attempted' => $this->dribbles_attempted,
            ]),
            
            'forward_stats' => $this->when($this->shots_on_target !== null, [
                'shots_on_target' => $this->shots_on_target,
                'shots_off_target' => $this->shots_off_target,
                'headers_won' => $this->headers_won,
                'headers_attempted' => $this->headers_attempted,
                'offsides' => $this->offsides,
            ]),
            
            // Additional Information
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id ?? null,
                    'name' => $this->school->name ?? null,
                ];
            }),
            'player' => new PlayerResource($this->whenLoaded('player')),
            'training' => new TrainingResource($this->whenLoaded('training')),
            'recorded_by_user' => new UserResource($this->whenLoaded('recordedBy')),
            
            // Calculated Fields
            'calculated_stats' => [
                'pass_accuracy_percentage' => $this->pass_accuracy,
                'shot_accuracy_percentage' => $this->shot_accuracy,
                'tackle_success_rate' => $this->tackles_attempted > 0 
                    ? round(($this->tackles_won / $this->tackles_attempted) * 100, 2) 
                    : null,
                'aerial_duel_success_rate' => $this->aerial_duels_attempted > 0 
                    ? round(($this->aerial_duels_won / $this->aerial_duels_attempted) * 100, 2) 
                    : null,
                'cross_accuracy' => $this->crosses_attempted > 0 
                    ? round(($this->crosses_completed / $this->crosses_attempted) * 100, 2) 
                    : null,
                'dribble_success_rate' => $this->dribbles_attempted > 0 
                    ? round(($this->dribbles_successful / $this->dribbles_attempted) * 100, 2) 
                    : null,
                'header_success_rate' => $this->headers_attempted > 0 
                    ? round(($this->headers_won / $this->headers_attempted) * 100, 2) 
                    : null,
            ],
        ];
    }
}