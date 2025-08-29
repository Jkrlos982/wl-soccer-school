<?php

namespace App\Transformers;

use App\Models\Category;
use League\Fractal\TransformerAbstract;

class CategoryTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     */
    protected array $availableIncludes = [
        'coach'
    ];

    /**
     * Transform the Category model
     */
    public function transform(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'min_age' => $category->min_age,
            'max_age' => $category->max_age,
            'gender' => $category->gender,
            'max_players' => $category->max_players,
            'training_days' => $category->training_days,
            'training_start_time' => $category->training_start_time,
            'training_end_time' => $category->training_end_time,
            'field_location' => $category->field_location,
            'is_active' => $category->is_active,
            'coach_id' => $category->coach_id,
            'school_id' => $category->school_id,
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
            'links' => [
                'self' => route('api.v1.categories.show', $category->id),
                'edit' => route('api.v1.categories.update', $category->id),
                'delete' => route('api.v1.categories.destroy', $category->id)
            ]
        ];
    }

    /**
     * Include Coach
     */
    public function includeCoach(Category $category)
    {
        if ($category->coach) {
            // TODO: Crear UserTransformer cuando esté disponible
            return $this->item($category->coach, function ($coach) {
                return [
                    'id' => $coach->id,
                    'name' => $coach->name,
                    'email' => $coach->email
                ];
            });
        }

        return null;
    }
}