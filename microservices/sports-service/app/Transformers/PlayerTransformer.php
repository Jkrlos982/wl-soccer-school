<?php

namespace App\Transformers;

use App\Models\Player;
use League\Fractal\TransformerAbstract;

class PlayerTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     */
    protected array $availableIncludes = [
        'category',
        'school'
    ];

    /**
     * Transform the Player model into an array.
     */
    public function transform(Player $player): array
    {
        return [
            'id' => $player->id,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'full_name' => $player->full_name,
            'display_name' => $player->display_name,
            'birth_date' => $player->birth_date?->format('Y-m-d'),
            'age' => $player->age,
            'gender' => $player->gender,
            'gender_display' => $player->gender === 'M' ? 'Masculino' : 'Femenino',
            'document_type' => $player->document_type,
            'document_number' => $player->document_number,
            'phone' => $player->phone,
            'email' => $player->email,
            'address' => $player->address,
            'emergency_contact_name' => $player->emergency_contact_name,
            'emergency_contact_phone' => $player->emergency_contact_phone,
            'emergency_contact_relationship' => $player->emergency_contact_relationship,
            'medical_conditions' => $player->medical_conditions,
            'allergies' => $player->allergies,
            'medications' => $player->medications,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'enrollment_date' => $player->enrollment_date?->format('Y-m-d'),
            'is_active' => $player->is_active,
            'status' => $player->is_active ? 'Activo' : 'Inactivo',
            'photo_url' => $player->getPhotoUrl(),
            'has_emergency_contact' => $player->hasEmergencyContact(),
            'has_medical_conditions' => $player->hasMedicalConditions(),
            'created_at' => $player->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $player->updated_at?->format('Y-m-d H:i:s'),
            'links' => [
                'self' => route('api.v1.players.show', $player->id),
                'edit' => route('api.v1.players.update', $player->id),
                'delete' => route('api.v1.players.destroy', $player->id),
                'statistics' => route('api.v1.players.statistics', $player->id),
                'upload_photo' => route('api.v1.players.upload-photo', $player->id),
            ]
        ];
    }

    /**
     * Include Category
     */
    public function includeCategory(Player $player)
    {
        if (!$player->category) {
            return null;
        }

        return $this->item($player->category, new CategoryTransformer());
    }

    /**
     * Include School
     */
    public function includeSchool(Player $player)
    {
        if (!$player->school) {
            return null;
        }

        // TODO: Create SchoolTransformer when School model is available
        return $this->primitive([
            'id' => $player->school->id ?? null,
            'name' => $player->school->name ?? null,
        ]);
    }
}