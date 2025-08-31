<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee');
        
        return [
            'employee_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_code')->ignore($employeeId)
            ],
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'document_type' => 'sometimes|in:CC,CE,PA,TI',
            'document_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('employees', 'document_number')->ignore($employeeId)
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'sometimes|date|before:today',
            'hire_date' => 'sometimes|date',
            'department_id' => 'sometimes|exists:departments,id',
            'position_id' => 'sometimes|exists:positions,id',
            'base_salary' => 'sometimes|numeric|min:0|max:999999999.99',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'status' => 'sometimes|in:active,inactive,terminated',
            'contract_type' => 'sometimes|in:indefinite,fixed_term,apprentice,contractor',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'termination_reason' => 'nullable|string|max:500|required_if:status,terminated'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_code.unique' => 'El código de empleado ya existe.',
            'document_type.in' => 'El tipo de documento debe ser CC, CE, PA o TI.',
            'document_number.unique' => 'El número de documento ya existe.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'El correo electrónico ya existe.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'position_id.exists' => 'El cargo seleccionado no existe.',
            'base_salary.numeric' => 'El salario base debe ser un número.',
            'base_salary.min' => 'El salario base debe ser mayor a 0.',
            'salary_type.in' => 'El tipo de salario debe ser mensual o por horas.',
            'status.in' => 'El estado debe ser activo, inactivo o terminado.',
            'contract_type.in' => 'El tipo de contrato debe ser indefinido, término fijo, aprendiz o contratista.',
            'termination_date.after_or_equal' => 'La fecha de terminación debe ser posterior o igual a la fecha de contratación.',
            'termination_reason.required_if' => 'La razón de terminación es obligatoria cuando el estado es terminado.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_code' => 'código de empleado',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'birth_date' => 'fecha de nacimiento',
            'hire_date' => 'fecha de contratación',
            'department_id' => 'departamento',
            'position_id' => 'cargo',
            'base_salary' => 'salario base',
            'salary_type' => 'tipo de salario',
            'hourly_rate' => 'tarifa por hora',
            'status' => 'estado',
            'contract_type' => 'tipo de contrato',
            'emergency_contact_name' => 'nombre contacto de emergencia',
            'emergency_contact_phone' => 'teléfono contacto de emergencia',
            'bank_account' => 'cuenta bancaria',
            'bank_name' => 'nombre del banco',
            'notes' => 'notas',
            'termination_date' => 'fecha de terminación',
            'termination_reason' => 'razón de terminación'
        ];
    }
}
