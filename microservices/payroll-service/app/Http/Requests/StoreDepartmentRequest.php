<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
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
            'name' => 'required|string|max:100|unique:departments,name',
            'code' => 'required|string|max:20|unique:departments,code|regex:/^[A-Z0-9_-]+$/',
            'description' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:employees,id',
            'parent_id' => 'nullable|exists:departments,id',
            'budget' => 'nullable|numeric|min:0|max:999999999.99',
            'cost_center' => 'nullable|string|max:50|unique:departments,cost_center',
            'location' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:20|regex:/^[+]?[0-9\s\-\(\)]+$/',
            'email' => 'nullable|email|max:100',
            'status' => 'sometimes|in:active,inactive',
            'established_date' => 'nullable|date|before_or_equal:today'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that manager is active if provided
            if ($this->has('manager_id') && $this->input('manager_id')) {
                $manager = \App\Models\Employee::find($this->input('manager_id'));
                if ($manager && $manager->status !== 'active') {
                    $validator->errors()->add('manager_id', 'El gerente debe estar activo.');
                }
            }

            // Validate that parent department is active if provided
            if ($this->has('parent_id') && $this->input('parent_id')) {
                $parent = \App\Models\Department::find($this->input('parent_id'));
                if ($parent && $parent->status !== 'active') {
                    $validator->errors()->add('parent_id', 'El departamento padre debe estar activo.');
                }
            }

            // Validate department code format
            if ($this->has('code')) {
                $code = strtoupper($this->input('code'));
                if (strlen($code) < 2) {
                    $validator->errors()->add('code', 'El código debe tener al menos 2 caracteres.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert code to uppercase
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->input('code'))
            ]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del departamento es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'name.unique' => 'Ya existe un departamento con este nombre.',
            'code.required' => 'El código del departamento es obligatorio.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede exceder 20 caracteres.',
            'code.unique' => 'Ya existe un departamento con este código.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'manager_id.exists' => 'El gerente seleccionado no existe.',
            'parent_id.exists' => 'El departamento padre seleccionado no existe.',
            'budget.numeric' => 'El presupuesto debe ser un número.',
            'budget.min' => 'El presupuesto no puede ser negativo.',
            'budget.max' => 'El presupuesto excede el límite permitido.',
            'cost_center.string' => 'El centro de costos debe ser una cadena de texto.',
            'cost_center.max' => 'El centro de costos no puede exceder 50 caracteres.',
            'cost_center.unique' => 'Ya existe un departamento con este centro de costos.',
            'location.string' => 'La ubicación debe ser una cadena de texto.',
            'location.max' => 'La ubicación no puede exceder 200 caracteres.',
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'phone.regex' => 'El formato del teléfono no es válido.',
            'email.email' => 'El formato del email no es válido.',
            'email.max' => 'El email no puede exceder 100 caracteres.',
            'status.in' => 'El estado debe ser activo o inactivo.',
            'established_date.date' => 'La fecha de establecimiento debe ser una fecha válida.',
            'established_date.before_or_equal' => 'La fecha de establecimiento no puede ser futura.'
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
            'name' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'manager_id' => 'gerente',
            'parent_id' => 'departamento padre',
            'budget' => 'presupuesto',
            'cost_center' => 'centro de costos',
            'location' => 'ubicación',
            'phone' => 'teléfono',
            'email' => 'email',
            'status' => 'estado',
            'established_date' => 'fecha de establecimiento'
        ];
    }
}
