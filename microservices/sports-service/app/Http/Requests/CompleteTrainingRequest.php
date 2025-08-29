<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTrainingRequest extends FormRequest
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
            'observations' => 'nullable|string|max:2000',
            'weather_conditions' => 'nullable|array',
            'weather_conditions.temperature' => 'nullable|numeric|between:-50,60',
            'weather_conditions.humidity' => 'nullable|numeric|between:0,100',
            'weather_conditions.conditions' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:300',
            'attendance' => 'nullable|array',
            'attendance.*.player_id' => 'required_with:attendance|exists:players,id',
            'attendance.*.status' => 'required_with:attendance|in:present,absent,late,excused',
            'attendance.*.arrival_time' => 'nullable|date_format:H:i',
            'attendance.*.notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el entrenamiento esté en progreso
            $training = $this->route('training');
            
            if ($training && $training->status !== 'in_progress') {
                $validator->errors()->add('status', 
                    'Solo se pueden completar entrenamientos que estén en progreso.');
            }
            
            // Validar que los jugadores pertenezcan a la categoría del entrenamiento
            if ($this->attendance && $training) {
                foreach ($this->attendance as $index => $attendanceData) {
                    if (isset($attendanceData['player_id'])) {
                        $player = \App\Models\Player::find($attendanceData['player_id']);
                        if ($player && $player->category_id !== $training->category_id) {
                            $validator->errors()->add(
                                "attendance.{$index}.player_id", 
                                'El jugador no pertenece a la categoría del entrenamiento.'
                            );
                        }
                    }
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
            'observations.max' => 'Las observaciones no pueden exceder 2000 caracteres.',
            'weather_conditions.temperature.numeric' => 'La temperatura debe ser un número.',
            'weather_conditions.temperature.between' => 'La temperatura debe estar entre -50 y 60 grados.',
            'weather_conditions.humidity.numeric' => 'La humedad debe ser un número.',
            'weather_conditions.humidity.between' => 'La humedad debe estar entre 0 y 100%.',
            'weather_conditions.conditions.max' => 'Las condiciones climáticas no pueden exceder 100 caracteres.',
            'duration_minutes.integer' => 'La duración debe ser un número entero.',
            'duration_minutes.min' => 'La duración mínima es 1 minuto.',
            'duration_minutes.max' => 'La duración máxima es 300 minutos.',
            'attendance.*.player_id.required_with' => 'El ID del jugador es obligatorio.',
            'attendance.*.player_id.exists' => 'El jugador seleccionado no existe.',
            'attendance.*.status.required_with' => 'El estado de asistencia es obligatorio.',
            'attendance.*.status.in' => 'El estado debe ser: presente, ausente, tarde o justificado.',
            'attendance.*.arrival_time.date_format' => 'La hora de llegada debe tener el formato HH:MM.',
            'attendance.*.notes.max' => 'Las notas no pueden exceder 500 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'observations' => 'observaciones',
            'weather_conditions' => 'condiciones climáticas',
            'duration_minutes' => 'duración en minutos',
            'attendance' => 'asistencia',
            'attendance.*.player_id' => 'jugador',
            'attendance.*.status' => 'estado de asistencia',
            'attendance.*.arrival_time' => 'hora de llegada',
            'attendance.*.notes' => 'notas'
        ];
    }
}