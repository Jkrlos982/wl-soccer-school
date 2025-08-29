<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'coach_id' => 'nullable|exists:users,id',
            'max_players' => 'required|integer|min:1|max:50',
            'current_players' => 'nullable|integer|min:0',
            'season' => 'required|string|max:20',
            'training_schedule' => 'nullable|json',
            'match_schedule' => 'nullable|json',
            'field_location' => 'nullable|string|max:255',
            'uniform_colors' => 'nullable|json',
            'equipment_list' => 'nullable|json',
            'budget' => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
            'monthly_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'registration_open' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del equipo es obligatorio.',
            'name.max' => 'El nombre del equipo no puede exceder 255 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'coach_id.exists' => 'El entrenador seleccionado no existe.',
            'max_players.required' => 'El número máximo de jugadores es obligatorio.',
            'max_players.min' => 'El número máximo de jugadores debe ser al menos 1.',
            'max_players.max' => 'El número máximo de jugadores no puede exceder 50.',
            'season.required' => 'La temporada es obligatoria.',
            'season.max' => 'La temporada no puede exceder 20 caracteres.',
            'budget.min' => 'El presupuesto no puede ser negativo.',
            'registration_fee.min' => 'La cuota de inscripción no puede ser negativa.',
            'monthly_fee.min' => 'La cuota mensual no puede ser negativa.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'registration_open' => $this->boolean('registration_open', true),
            'current_players' => $this->input('current_players', 0),
        ]);
    }
}
