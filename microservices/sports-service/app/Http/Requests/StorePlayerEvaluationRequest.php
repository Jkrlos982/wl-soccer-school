<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlayerEvaluation;

class StorePlayerEvaluationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return PlayerEvaluation::validationRules();
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return PlayerEvaluation::validationMessages();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure evaluation_date is properly formatted
        if ($this->has('evaluation_date') && is_string($this->evaluation_date)) {
            $this->merge([
                'evaluation_date' => date('Y-m-d', strtotime($this->evaluation_date))
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that at least one skill rating is provided
            $skillFields = [
                'technical_skills', 'ball_control', 'passing', 'shooting', 'dribbling',
                'speed', 'endurance', 'strength', 'agility',
                'positioning', 'decision_making', 'teamwork', 'game_understanding',
                'attitude', 'discipline', 'leadership', 'commitment'
            ];

            $hasSkillRating = false;
            foreach ($skillFields as $field) {
                if ($this->filled($field)) {
                    $hasSkillRating = true;
                    break;
                }
            }

            if (!$hasSkillRating) {
                $validator->errors()->add('skills', 'Debe proporcionar al menos una calificación de habilidad.');
            }

            // Validate that evaluation_date is not in the future
            if ($this->filled('evaluation_date')) {
                $evaluationDate = \Carbon\Carbon::parse($this->evaluation_date);
                if ($evaluationDate->isFuture()) {
                    $validator->errors()->add('evaluation_date', 'La fecha de evaluación no puede ser futura.');
                }
            }

            // If training_id is provided, validate it belongs to the same school
            if ($this->filled('training_id') && $this->filled('player_id')) {
                $training = \App\Models\Training::find($this->training_id);
                $player = \App\Models\Player::find($this->player_id);
                
                if ($training && $player && $training->school_id !== $player->school_id) {
                    $validator->errors()->add('training_id', 'El entrenamiento debe pertenecer a la misma escuela que el jugador.');
                }
            }
        });
    }
}