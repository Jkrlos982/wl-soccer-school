<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
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
            'school_id' => 'required|integer|min:1',
            'player_id' => 'required|integer|min:1',
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|array',
            'allergies.*' => 'string|max:255',
            'medications' => 'nullable|array',
            'medications.*' => 'string|max:255',
            'emergency_contacts' => 'required|array|min:1',
            'emergency_contacts.*.name' => 'required|string|max:255',
            'emergency_contacts.*.relationship' => 'required|string|max:100',
            'emergency_contacts.*.phone' => 'required|string|max:20',
            'emergency_contacts.*.email' => 'nullable|email|max:255',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'primary_doctor_name' => 'nullable|string|max:255',
            'primary_doctor_phone' => 'nullable|string|max:20',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:500',
            'notes' => 'nullable|string|max:1000',
            'consent_given' => 'boolean',
            'consent_given_by' => 'nullable|integer|min:1',
            'consent_date' => 'nullable|date'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'school_id.required' => 'El ID de la escuela es obligatorio.',
            'player_id.required' => 'El ID del jugador es obligatorio.',
            'blood_type.in' => 'El tipo de sangre debe ser uno de los valores válidos: A+, A-, B+, B-, AB+, AB-, O+, O-.',
            'emergency_contacts.required' => 'Al menos un contacto de emergencia es obligatorio.',
            'emergency_contacts.min' => 'Debe proporcionar al menos un contacto de emergencia.',
            'emergency_contacts.*.name.required' => 'El nombre del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.relationship.required' => 'La relación del contacto de emergencia es obligatoria.',
            'emergency_contacts.*.phone.required' => 'El teléfono del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.email.email' => 'El email del contacto de emergencia debe ser válido.',
            'height.numeric' => 'La altura debe ser un número.',
            'height.min' => 'La altura debe ser mayor a 0.',
            'height.max' => 'La altura no puede ser mayor a 300 cm.',
            'weight.numeric' => 'El peso debe ser un número.',
            'weight.min' => 'El peso debe ser mayor a 0.',
            'weight.max' => 'El peso no puede ser mayor a 500 kg.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'school_id' => 'ID de escuela',
            'player_id' => 'ID de jugador',
            'blood_type' => 'tipo de sangre',
            'allergies' => 'alergias',
            'medications' => 'medicamentos',
            'emergency_contacts' => 'contactos de emergencia',
            'insurance_provider' => 'proveedor de seguro',
            'insurance_policy_number' => 'número de póliza',
            'primary_doctor_name' => 'nombre del médico principal',
            'primary_doctor_phone' => 'teléfono del médico principal',
            'height' => 'altura',
            'weight' => 'peso',
            'notes' => 'notas',
            'consent_given' => 'consentimiento otorgado',
            'consent_given_by' => 'consentimiento otorgado por',
            'consent_date' => 'fecha de consentimiento'
        ];
    }
}