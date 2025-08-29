<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Training;

class UpdateTrainingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'date' => 'sometimes|required|date|after_or_equal:today',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'location' => 'sometimes|required|string|max:200',
            'type' => 'sometimes|required|in:training,match,friendly,tournament',
            'objectives' => 'nullable|string|max:1000',
            'activities' => 'nullable|string|max:2000',
            'observations' => 'nullable|string|max:2000',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'coach_id' => 'sometimes|required|exists:users,id',
            'weather_conditions' => 'nullable|array',
            'weather_conditions.temperature' => 'nullable|numeric',
            'weather_conditions.humidity' => 'nullable|numeric',
            'weather_conditions.conditions' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:300'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que no haya conflictos de horario (excluyendo el entrenamiento actual)
            if ($this->category_id && $this->date && $this->start_time && $this->end_time) {
                $training = $this->route('training');
                
                $conflict = Training::where('category_id', $this->category_id)
                    ->where('date', $this->date)
                    ->where('status', '!=', 'cancelled')
                    ->where('id', '!=', $training->id) // Excluir el entrenamiento actual
                    ->where(function($q) {
                        $q->whereBetween('start_time', [$this->start_time, $this->end_time])
                          ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                          ->orWhere(function($q2) {
                              $q2->where('start_time', '<=', $this->start_time)
                                 ->where('end_time', '>=', $this->end_time);
                          });
                    })
                    ->exists();
                    
                if ($conflict) {
                    $validator->errors()->add('start_time', 
                        'Ya existe un entrenamiento programado en este horario.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'location.required' => 'La ubicación es obligatoria.',
            'location.max' => 'La ubicación no puede exceder 200 caracteres.',
            'type.required' => 'El tipo de entrenamiento es obligatorio.',
            'type.in' => 'El tipo debe ser: entrenamiento, partido, amistoso o torneo.',
            'objectives.max' => 'Los objetivos no pueden exceder 1000 caracteres.',
            'activities.max' => 'Las actividades no pueden exceder 2000 caracteres.',
            'observations.max' => 'Las observaciones no pueden exceder 2000 caracteres.',
            'status.in' => 'El estado debe ser: programado, en progreso, completado o cancelado.',
            'coach_id.required' => 'El entrenador es obligatorio.',
            'coach_id.exists' => 'El entrenador seleccionado no existe.',
            'weather_conditions.temperature.numeric' => 'La temperatura debe ser un número.',
            'weather_conditions.humidity.numeric' => 'La humedad debe ser un número.',
            'weather_conditions.conditions.max' => 'Las condiciones climáticas no pueden exceder 100 caracteres.',
            'duration_minutes.integer' => 'La duración debe ser un número entero.',
            'duration_minutes.min' => 'La duración mínima es 1 minuto.',
            'duration_minutes.max' => 'La duración máxima es 300 minutos.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'date' => 'fecha',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'location' => 'ubicación',
            'type' => 'tipo',
            'objectives' => 'objetivos',
            'activities' => 'actividades',
            'observations' => 'observaciones',
            'status' => 'estado',
            'coach_id' => 'entrenador',
            'weather_conditions' => 'condiciones climáticas',
            'duration_minutes' => 'duración en minutos'
        ];
    }
}