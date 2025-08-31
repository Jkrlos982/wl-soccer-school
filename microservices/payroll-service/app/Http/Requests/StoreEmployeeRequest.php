<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'document_type' => 'required|in:CC,CE,PA,TI',
            'document_number' => 'required|string|max:20|unique:employees,document_number',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'required|date|before:today',
            'hire_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'base_salary' => 'required|numeric|min:0|max:999999999.99',
            'salary_type' => 'required|in:monthly,hourly',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99|required_if:salary_type,hourly',
            'status' => 'required|in:active,inactive,terminated',
            'contract_type' => 'required|in:indefinite,fixed_term,apprentice,contractor',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000'
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
            'employee_code.required' => 'El código de empleado es obligatorio.',
            'employee_code.unique' => 'El código de empleado ya existe.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.in' => 'El tipo de documento debe ser CC, CE, PA o TI.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'El número de documento ya existe.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'El correo electrónico ya existe.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'hire_date.required' => 'La fecha de contratación es obligatoria.',
            'department_id.required' => 'El departamento es obligatorio.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'position_id.required' => 'El cargo es obligatorio.',
            'position_id.exists' => 'El cargo seleccionado no existe.',
            'base_salary.required' => 'El salario base es obligatorio.',
            'base_salary.numeric' => 'El salario base debe ser un número.',
            'base_salary.min' => 'El salario base debe ser mayor a 0.',
            'salary_type.required' => 'El tipo de salario es obligatorio.',
            'salary_type.in' => 'El tipo de salario debe ser mensual o por horas.',
            'hourly_rate.required_if' => 'La tarifa por hora es obligatoria cuando el tipo de salario es por horas.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser activo, inactivo o terminado.',
            'contract_type.required' => 'El tipo de contrato es obligatorio.',
            'contract_type.in' => 'El tipo de contrato debe ser indefinido, término fijo, aprendiz o contratista.'
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
            'notes' => 'notas'
        ];
    }
}
