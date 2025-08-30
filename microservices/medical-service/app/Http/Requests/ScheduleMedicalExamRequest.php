<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMedicalExamRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_type' => 'required|string|max:100|in:annual,pre_season,injury_assessment,return_to_play,routine,specialized',
            'exam_date' => 'required|date|after:now',
            'doctor_name' => 'required|string|max:255',
            'doctor_phone' => 'nullable|string|max:20',
            'doctor_email' => 'nullable|email|max:255',
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:255',
            'estimated_duration' => 'nullable|integer|min:15|max:480', // 15 minutes to 8 hours
            'cost' => 'nullable|numeric|min:0',
            'insurance_covered' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'exam_type.required' => 'El tipo de examen es obligatorio.',
            'exam_type.in' => 'El tipo de examen debe ser: anual, pre-temporada, evaluación de lesión, regreso al juego, rutina o especializado.',
            'exam_date.required' => 'La fecha del examen es obligatoria.',
            'exam_date.date' => 'La fecha del examen debe ser válida.',
            'exam_date.after' => 'La fecha del examen debe ser posterior a la fecha actual.',
            'doctor_name.required' => 'El nombre del médico es obligatorio.',
            'doctor_name.max' => 'El nombre del médico no puede exceder 255 caracteres.',
            'doctor_phone.max' => 'El teléfono del médico no puede exceder 20 caracteres.',
            'doctor_email.email' => 'El email del médico debe ser válido.',
            'clinic_name.max' => 'El nombre de la clínica no puede exceder 255 caracteres.',
            'clinic_address.max' => 'La dirección de la clínica no puede exceder 500 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'requirements.*.max' => 'Cada requisito no puede exceder 255 caracteres.',
            'estimated_duration.integer' => 'La duración estimada debe ser un número entero.',
            'estimated_duration.min' => 'La duración estimada debe ser al menos 15 minutos.',
            'estimated_duration.max' => 'La duración estimada no puede exceder 480 minutos (8 horas).',
            'cost.numeric' => 'El costo debe ser un número.',
            'cost.min' => 'El costo debe ser mayor o igual a 0.',
            'insurance_covered.boolean' => 'La cobertura de seguro debe ser verdadero o falso.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'exam_type' => 'tipo de examen',
            'exam_date' => 'fecha del examen',
            'doctor_name' => 'nombre del médico',
            'doctor_phone' => 'teléfono del médico',
            'doctor_email' => 'email del médico',
            'clinic_name' => 'nombre de la clínica',
            'clinic_address' => 'dirección de la clínica',
            'notes' => 'notas',
            'requirements' => 'requisitos',
            'estimated_duration' => 'duración estimada',
            'cost' => 'costo',
            'insurance_covered' => 'cubierto por seguro'
        ];
    }
}