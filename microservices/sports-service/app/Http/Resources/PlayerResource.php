<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'gender' => $this->gender,
            'gender_display' => $this->gender === 'M' ? 'Masculino' : 'Femenino',
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
                'relationship' => $this->emergency_contact_relationship,
            ],
            'medical_info' => [
                'conditions' => $this->medical_conditions,
                'allergies' => $this->allergies,
                'medications' => $this->medications,
                'has_conditions' => $this->hasMedicalConditions(),
            ],
            'sports_info' => [
                'position' => $this->position,
                'jersey_number' => $this->jersey_number,
                'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),
            ],
            'status' => [
                'is_active' => $this->is_active,
                'display' => $this->is_active ? 'Activo' : 'Inactivo',
            ],
            'photo_url' => $this->getPhotoUrl(),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'description' => $this->category->description,
                    'min_age' => $this->category->min_age,
                    'max_age' => $this->category->max_age,
                    'gender' => $this->category->gender,
                ];
            }),
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id ?? null,
                    'name' => $this->school->name ?? null,
                ];
            }),
            'eligibility' => [
                'is_eligible_for_category' => $this->category ? $this->isEligibleForCategory($this->category) : null,
                'has_emergency_contact' => $this->hasEmergencyContact(),
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            'links' => [
                'self' => route('api.v1.players.show', $this->id),
                'edit' => route('api.v1.players.update', $this->id),
                'delete' => route('api.v1.players.destroy', $this->id),
                'statistics' => route('api.v1.players.statistics', $this->id),
                'upload_photo' => route('api.v1.players.upload-photo', $this->id),
            ]
        ];
    }
}