<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCertificateRequest extends FormRequest
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
            'certificate_type' => 'required|string|max:100|in:fitness_to_play,medical_clearance,injury_report,return_to_play,vaccination,physical_exam,sports_participation,medical_exemption',
            'purpose' => 'required|string|max:255',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'restrictions' => 'nullable|array',
            'restrictions.*' => 'string|max:255',
            'recommendations' => 'nullable|array',
            'recommendations.*' => 'string|max:255',
            'medical_conditions' => 'nullable|array',
            'medical_conditions.*' => 'string|max:255',
            'medications_affecting_performance' => 'nullable|array',
            'medications_affecting_performance.*' => 'string|max:255',
            'doctor_name' => 'required|string|max:255',
            'doctor_license' => 'required|string|max:100',
            'doctor_phone' => 'nullable|string|max:20',
            'doctor_email' => 'nullable|email|max:255',
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:500',
            'examination_date' => 'required|date|before_or_equal:now',
            'next_examination_due' => 'nullable|date|after:examination_date',
            'fitness_level' => 'nullable|string|in:excellent,good,fair,poor,restricted',
            'clearance_status' => 'required|string|in:cleared,cleared_with_restrictions,not_cleared,pending_further_evaluation',
            'notes' => 'nullable|string|max:1000',
            'emergency_contact_required' => 'boolean',
            'parent_guardian_signature_required' => 'boolean',
            'insurance_verification' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'certificate_type.required' => 'El tipo de certificado es obligatorio.',
            'certificate_type.in' => 'El tipo de certificado debe ser uno de los valores válidos.',
            'purpose.required' => 'El propósito del certificado es obligatorio.',
            'purpose.max' => 'El propósito no puede exceder 255 caracteres.',
            'valid_from.date' => 'La fecha de inicio de validez debe ser válida.',
            'valid_until.date' => 'La fecha de fin de validez debe ser válida.',
            'valid_until.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'restrictions.*.max' => 'Cada restricción no puede exceder 255 caracteres.',
            'recommendations.*.max' => 'Cada recomendación no puede exceder 255 caracteres.',
            'medical_conditions.*.max' => 'Cada condición médica no puede exceder 255 caracteres.',
            'medications_affecting_performance.*.max' => 'Cada medicamento no puede exceder 255 caracteres.',
            'doctor_name.required' => 'El nombre del médico es obligatorio.',
            'doctor_name.max' => 'El nombre del médico no puede exceder 255 caracteres.',
            'doctor_license.required' => 'La licencia del médico es obligatoria.',
            'doctor_license.max' => 'La licencia del médico no puede exceder 100 caracteres.',
            'doctor_phone.max' => 'El teléfono del médico no puede exceder 20 caracteres.',
            'doctor_email.email' => 'El email del médico debe ser válido.',
            'clinic_name.max' => 'El nombre de la clínica no puede exceder 255 caracteres.',
            'clinic_address.max' => 'La dirección de la clínica no puede exceder 500 caracteres.',
            'examination_date.required' => 'La fecha de examen es obligatoria.',
            'examination_date.date' => 'La fecha de examen debe ser válida.',
            'examination_date.before_or_equal' => 'La fecha de examen no puede ser futura.',
            'next_examination_due.date' => 'La fecha del próximo examen debe ser válida.',
            'next_examination_due.after' => 'La fecha del próximo examen debe ser posterior al examen actual.',
            'fitness_level.in' => 'El nivel de condición física debe ser: excelente, bueno, regular, pobre o restringido.',
            'clearance_status.required' => 'El estado de autorización es obligatorio.',
            'clearance_status.in' => 'El estado de autorización debe ser: autorizado, autorizado con restricciones, no autorizado o pendiente de evaluación.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'certificate_type' => 'tipo de certificado',
            'purpose' => 'propósito',
            'valid_from' => 'válido desde',
            'valid_until' => 'válido hasta',
            'restrictions' => 'restricciones',
            'recommendations' => 'recomendaciones',
            'medical_conditions' => 'condiciones médicas',
            'medications_affecting_performance' => 'medicamentos que afectan el rendimiento',
            'doctor_name' => 'nombre del médico',
            'doctor_license' => 'licencia del médico',
            'doctor_phone' => 'teléfono del médico',
            'doctor_email' => 'email del médico',
            'clinic_name' => 'nombre de la clínica',
            'clinic_address' => 'dirección de la clínica',
            'examination_date' => 'fecha de examen',
            'next_examination_due' => 'próximo examen debido',
            'fitness_level' => 'nivel de condición física',
            'clearance_status' => 'estado de autorización',
            'notes' => 'notas',
            'emergency_contact_required' => 'contacto de emergencia requerido',
            'parent_guardian_signature_required' => 'firma de padre/tutor requerida',
            'insurance_verification' => 'verificación de seguro'
        ];
    }
}