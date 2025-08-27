<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ConceptTemplate;

class UpdateConceptTemplateRequest extends FormRequest
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
        $templateId = $this->route('template') ?? $this->route('id');
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'min:2',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('concept_templates', 'code')->ignore($templateId)
            ],
            'type' => [
                'sometimes',
                'required',
                Rule::in(['income', 'expense'])
            ],
            'category' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'min:2'
            ],
            'default_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del template es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres.',
            
            'code.required' => 'El código del template es obligatorio.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede exceder los 100 caracteres.',
            'code.min' => 'El código debe tener al menos 2 caracteres.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos.',
            'code.unique' => 'Ya existe un template con este código.',
            
            'type.required' => 'El tipo de template es obligatorio.',
            'type.in' => 'El tipo debe ser ingreso (income) o gasto (expense).',
            
            'category.required' => 'La categoría es obligatoria.',
            'category.string' => 'La categoría debe ser una cadena de texto.',
            'category.max' => 'La categoría no puede exceder los 100 caracteres.',
            'category.min' => 'La categoría debe tener al menos 2 caracteres.',
            
            'default_amount.numeric' => 'El monto por defecto debe ser un número.',
            'default_amount.min' => 'El monto por defecto debe ser mayor o igual a 0.',
            
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            
            'metadata.array' => 'Los metadatos deben ser un objeto JSON válido.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'code' => 'código',
            'type' => 'tipo',
            'category' => 'categoría',
            'default_amount' => 'monto por defecto',
            'is_active' => 'estado activo',
            'metadata' => 'metadatos'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir el código a mayúsculas y limpiar espacios
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code))
            ]);
        }

        // Limpiar espacios en el nombre
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name)
            ]);
        }

        // Limpiar espacios en la descripción
        if ($this->has('description')) {
            $this->merge([
                'description' => trim($this->description)
            ]);
        }

        // Limpiar espacios en la categoría
        if ($this->has('category')) {
            $this->merge([
                'category' => trim($this->category)
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validación personalizada: verificar que el código no contenga palabras reservadas
            $reservedWords = ['SYSTEM', 'ADMIN', 'ROOT', 'DEFAULT', 'NULL', 'UNDEFINED'];
            
            if ($this->has('code')) {
                $code = strtoupper($this->code);
                foreach ($reservedWords as $word) {
                    if (strpos($code, $word) !== false) {
                        $validator->errors()->add('code', "El código no puede contener la palabra reservada: {$word}");
                        break;
                    }
                }
            }

            // Validación personalizada: verificar coherencia entre tipo y categoría
            if ($this->has('type') && $this->has('category')) {
                $type = $this->type;
                $category = $this->category;
                
                $incomeCategories = ['tuition', 'enrollment', 'other_income'];
                $expenseCategories = ['salaries', 'services', 'supplies', 'maintenance', 'other_expenses'];
                
                if ($type === 'income' && !in_array($category, $incomeCategories)) {
                    $validator->errors()->add('category', 'La categoría seleccionada no es válida para templates de ingreso.');
                }
                
                if ($type === 'expense' && !in_array($category, $expenseCategories)) {
                    $validator->errors()->add('category', 'La categoría seleccionada no es válida para templates de gasto.');
                }
            }

            // Validación personalizada: no permitir modificar templates del sistema
            $templateId = $this->route('template') ?? $this->route('id');
            if ($templateId) {
                $template = ConceptTemplate::find($templateId);
                if ($template && $template->is_system) {
                    // Solo permitir cambiar el estado activo en templates del sistema
                    $allowedFields = ['is_active'];
                    $requestFields = array_keys($this->all());
                    $restrictedFields = array_diff($requestFields, $allowedFields);
                    
                    if (!empty($restrictedFields)) {
                        $validator->errors()->add('general', 'No se pueden modificar los templates del sistema, solo su estado activo.');
                    }
                }
            }
        });
    }
}