<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlayerEvaluation;

class UpdatePlayerEvaluationRequest extends FormRequest
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
        $rules = PlayerEvaluation::validationRules();
        
        // Make player_id optional for updates
        $rules['player_id'] = 'sometimes|exists:players,id';
        
        return $rules;
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
            // Validate that evaluation_date is not in the future
            if ($this->filled('evaluation_date')) {
                $evaluationDate = \Carbon\Carbon::parse($this->evaluation_date);
                if ($evaluationDate->isFuture()) {
                    $validator->errors()->add('evaluation_date', 'La fecha de evaluación no puede ser futura.');
                }
            }

            // If training_id is provided, validate it belongs to the same school
            if ($this->filled('training_id')) {
                $evaluation = $this->route('evaluation');
                $training = \App\Models\Training::find($this->training_id);
                
                if ($training && $evaluation && $training->school_id !== $evaluation->school_id) {
                    $validator->errors()->add('training_id', 'El entrenamiento debe pertenecer a la misma escuela que la evaluación.');
                }
            }
        });
    }
}