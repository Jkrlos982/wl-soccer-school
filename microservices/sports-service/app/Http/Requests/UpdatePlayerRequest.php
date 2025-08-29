<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends FormRequest
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
        $playerId = $this->route('player')->id ?? $this->route('player');
        
        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'birth_date' => 'sometimes|required|date|before:today',
            'gender' => 'sometimes|required|in:M,F',
            'document_type' => 'sometimes|required|in:CC,TI,CE,PP',
            'document_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('players', 'document_number')->ignore($playerId)
            ],
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'emergency_contact_name' => 'sometimes|required|string|max:100',
            'emergency_contact_phone' => 'sometimes|required|string|max:20',
            'emergency_contact_relationship' => 'sometimes|required|string|max:50',
            'medical_conditions' => 'sometimes|nullable|string|max:1000',
            'allergies' => 'sometimes|nullable|string|max:500',
            'medications' => 'sometimes|nullable|string|max:500',
            'position' => 'sometimes|nullable|string|max:50',
            'jersey_number' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('players', 'jersey_number')
                    ->ignore($playerId)
                    ->where('category_id', $this->input('category_id', $this->route('player')->category_id ?? null))
                    ->where('is_active', true)
            ],
            'enrollment_date' => 'sometimes|nullable|date|before_or_equal:today',
            'is_active' => 'sometimes|boolean',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.max' => 'El nombre no puede tener más de 100 caracteres.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.max' => 'El apellido no puede tener más de 100 caracteres.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'gender.required' => 'El género es obligatorio.',
            'gender.in' => 'El género debe ser Masculino (M) o Femenino (F).',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.in' => 'El tipo de documento debe ser CC, TI, CE o PP.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ya existe un jugador con este número de documento.',
            'document_number.max' => 'El número de documento no puede tener más de 20 caracteres.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'address.max' => 'La dirección no puede tener más de 255 caracteres.',
            'emergency_contact_name.required' => 'El nombre del contacto de emergencia es obligatorio.',
            'emergency_contact_name.max' => 'El nombre del contacto de emergencia no puede tener más de 100 caracteres.',
            'emergency_contact_phone.required' => 'El teléfono del contacto de emergencia es obligatorio.',
            'emergency_contact_phone.max' => 'El teléfono del contacto de emergencia no puede tener más de 20 caracteres.',
            'emergency_contact_relationship.required' => 'La relación del contacto de emergencia es obligatoria.',
            'emergency_contact_relationship.max' => 'La relación del contacto de emergencia no puede tener más de 50 caracteres.',
            'medical_conditions.max' => 'Las condiciones médicas no pueden tener más de 1000 caracteres.',
            'allergies.max' => 'Las alergias no pueden tener más de 500 caracteres.',
            'medications.max' => 'Los medicamentos no pueden tener más de 500 caracteres.',
            'position.max' => 'La posición no puede tener más de 50 caracteres.',
            'jersey_number.integer' => 'El número de camiseta debe ser un número entero.',
            'jersey_number.min' => 'El número de camiseta debe ser mayor a 0.',
            'jersey_number.max' => 'El número de camiseta no puede ser mayor a 999.',
            'jersey_number.unique' => 'Ya existe un jugador activo con este número de camiseta en la categoría.',
            'enrollment_date.date' => 'La fecha de inscripción debe ser una fecha válida.',
            'enrollment_date.before_or_equal' => 'La fecha de inscripción no puede ser futura.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg.',
            'photo.max' => 'La imagen no puede ser mayor a 2MB.'
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'birth_date' => 'fecha de nacimiento',
            'gender' => 'género',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'phone' => 'teléfono',
            'email' => 'email',
            'address' => 'dirección',
            'emergency_contact_name' => 'nombre del contacto de emergencia',
            'emergency_contact_phone' => 'teléfono del contacto de emergencia',
            'emergency_contact_relationship' => 'relación del contacto de emergencia',
            'medical_conditions' => 'condiciones médicas',
            'allergies' => 'alergias',
            'medications' => 'medicamentos',
            'position' => 'posición',
            'jersey_number' => 'número de camiseta',
            'enrollment_date' => 'fecha de inscripción',
            'photo' => 'foto'
        ];
    }
}
