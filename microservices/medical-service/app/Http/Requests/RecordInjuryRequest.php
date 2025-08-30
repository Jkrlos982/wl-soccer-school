<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordInjuryRequest extends FormRequest
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
            'injury_type' => 'required|string|max:100|in:sprain,strain,fracture,contusion,laceration,concussion,dislocation,tear,overuse,acute,chronic,other',
            'body_part' => 'required|string|max:100',
            'severity' => 'required|in:minor,moderate,severe,critical',
            'injury_datetime' => 'required|date|before_or_equal:today',
            'injury_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|max:1000',
            'cause' => 'nullable|string|max:500',
            'activity_when_injured' => 'nullable|string|max:255',
            'treatment_required' => 'boolean',
            'immediate_treatment' => 'nullable|string|max:500',
            'estimated_recovery_time' => 'nullable|integer|min:1|max:365', // days
            'return_to_play_date' => 'nullable|date|after:injury_date',
            'doctor_seen' => 'boolean',
            'doctor_name' => 'nullable|string|max:255',
            'hospital_visit' => 'boolean',
            'hospital_name' => 'nullable|string|max:255',
            'imaging_required' => 'boolean',
            'imaging_type' => 'nullable|string|max:100',
            'imaging_results' => 'nullable|string|max:1000',
            'medication_prescribed' => 'nullable|array',
            'medication_prescribed.*' => 'string|max:255',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:injury_date',
            'parent_notified' => 'boolean',
            'parent_notification_date' => 'nullable|date',
            'insurance_claim' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'injury_type.required' => 'El tipo de lesión es obligatorio.',
            'injury_type.in' => 'El tipo de lesión debe ser uno de los valores válidos.',
            'body_part.required' => 'La parte del cuerpo afectada es obligatoria.',
            'severity.required' => 'La severidad de la lesión es obligatoria.',
            'severity.in' => 'La severidad debe ser: menor, moderada, severa o crítica.',
            'injury_datetime.required' => 'La fecha de la lesión es obligatoria.',
            'injury_datetime.date' => 'La fecha de la lesión debe ser una fecha válida.',
            'injury_datetime.before_or_equal' => 'La fecha de la lesión no puede ser futura.',
            'injury_time.date_format' => 'La hora de la lesión debe tener formato HH:MM.',
            'description.required' => 'La descripción de la lesión es obligatoria.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'cause.max' => 'La causa no puede exceder 500 caracteres.',
            'activity_when_injured.max' => 'La actividad durante la lesión no puede exceder 255 caracteres.',
            'immediate_treatment.max' => 'El tratamiento inmediato no puede exceder 500 caracteres.',
            'estimated_recovery_time.integer' => 'El tiempo estimado de recuperación debe ser un número entero.',
            'estimated_recovery_time.min' => 'El tiempo estimado de recuperación debe ser al menos 1 día.',
            'estimated_recovery_time.max' => 'El tiempo estimado de recuperación no puede exceder 365 días.',
            'return_to_play_date.date' => 'La fecha de regreso al juego debe ser válida.',
            'return_to_play_date.after' => 'La fecha de regreso al juego debe ser posterior a la fecha de lesión.',
            'doctor_name.max' => 'El nombre del médico no puede exceder 255 caracteres.',
            'hospital_name.max' => 'El nombre del hospital no puede exceder 255 caracteres.',
            'imaging_type.max' => 'El tipo de imagen no puede exceder 100 caracteres.',
            'imaging_results.max' => 'Los resultados de imagen no pueden exceder 1000 caracteres.',
            'medication_prescribed.*.max' => 'Cada medicamento no puede exceder 255 caracteres.',
            'follow_up_date.date' => 'La fecha de seguimiento debe ser válida.',
            'follow_up_date.after' => 'La fecha de seguimiento debe ser posterior a la fecha de lesión.',
            'parent_notification_date.date' => 'La fecha de notificación a padres debe ser válida.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'injury_type' => 'tipo de lesión',
            'body_part' => 'parte del cuerpo',
            'severity' => 'severidad',
            'injury_date' => 'fecha de lesión',
            'injury_time' => 'hora de lesión',
            'description' => 'descripción',
            'cause' => 'causa',
            'activity_when_injured' => 'actividad durante lesión',
            'treatment_required' => 'tratamiento requerido',
            'immediate_treatment' => 'tratamiento inmediato',
            'estimated_recovery_time' => 'tiempo estimado de recuperación',
            'return_to_play_date' => 'fecha de regreso al juego',
            'doctor_seen' => 'visto por médico',
            'doctor_name' => 'nombre del médico',
            'hospital_visit' => 'visita al hospital',
            'hospital_name' => 'nombre del hospital',
            'imaging_required' => 'imagen requerida',
            'imaging_type' => 'tipo de imagen',
            'imaging_results' => 'resultados de imagen',
            'medication_prescribed' => 'medicamento prescrito',
            'follow_up_required' => 'seguimiento requerido',
            'follow_up_date' => 'fecha de seguimiento',
            'parent_notified' => 'padres notificados',
            'parent_notification_date' => 'fecha de notificación a padres',
            'insurance_claim' => 'reclamo de seguro',
            'notes' => 'notas'
        ];
    }
}