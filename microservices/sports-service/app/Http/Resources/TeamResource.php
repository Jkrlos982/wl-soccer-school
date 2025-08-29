<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'coach_id' => $this->coach_id,
            'coach' => $this->whenLoaded('coach', function () {
                return [
                    'id' => $this->coach->id,
                    'name' => $this->coach->name,
                    'email' => $this->coach->email,
                ];
            }),
            'max_players' => $this->max_players,
            'current_players' => $this->current_players,
            'season' => $this->season,
            'training_schedule' => $this->training_schedule ? json_decode($this->training_schedule, true) : null,
            'match_schedule' => $this->match_schedule ? json_decode($this->match_schedule, true) : null,
            'field_location' => $this->field_location,
            'uniform_colors' => $this->uniform_colors ? json_decode($this->uniform_colors, true) : null,
            'equipment_list' => $this->equipment_list ? json_decode($this->equipment_list, true) : null,
            'budget' => $this->budget ? (float) $this->budget : null,
            'registration_fee' => $this->registration_fee ? (float) $this->registration_fee : null,
            'monthly_fee' => $this->monthly_fee ? (float) $this->monthly_fee : null,
            'is_active' => (bool) $this->is_active,
            'registration_open' => (bool) $this->registration_open,
            'notes' => $this->notes,
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'players_count' => $this->when($this->relationLoaded('players'), function () {
                return $this->players->count();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
