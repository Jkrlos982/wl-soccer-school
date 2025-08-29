<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Training;

class StoreTrainingRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:200',
            'type' => 'required|in:training,match,friendly,tournament',
            'objectives' => 'nullable|string|max:1000',
            'activities' => 'nullable|string|max:2000',
            'coach_id' => 'required|exists:users,id',
            'weather_conditions' => 'nullable|array',
            'weather_conditions.temperature' => 'nullable|numeric',
            'weather_conditions.humidity' => 'nullable|numeric',
            'weather_conditions.conditions' => 'nullable|string|max:100'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que no haya conflictos de horario
            if ($this->category_id && $this->date && $this->start_time && $this->end_time) {
                $conflict = Training::where('category_id', $this->category_id)
                    ->where('date', $this->date)
                    ->where('status', '!=', 'cancelled')
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
            'coach_id.required' => 'El entrenador es obligatorio.',
            'coach_id.exists' => 'El entrenador seleccionado no existe.',
            'weather_conditions.temperature.numeric' => 'La temperatura debe ser un número.',
            'weather_conditions.humidity.numeric' => 'La humedad debe ser un número.',
            'weather_conditions.conditions.max' => 'Las condiciones climáticas no pueden exceder 100 caracteres.'
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
            'coach_id' => 'entrenador',
            'weather_conditions' => 'condiciones climáticas'
        ];
    }
}