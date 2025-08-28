<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\FinancialConcept;

class CreateFinancialConceptRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                'min:2',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('financial_concepts', 'code')
            ],
            'type' => [
                'required',
                Rule::in(['income', 'expense'])
            ],
            'category' => [
                'required',
                'string',
                'max:100',
                'min:2',
                Rule::in(['tuition', 'enrollment', 'other_income', 'salaries', 'services', 'supplies', 'maintenance', 'other_expenses'])
            ],
            'school_id' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'template_id' => [
                'nullable',
                'integer',
                'exists:concept_templates,id'
            ],
            'is_active' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del concepto financiero es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres.',
            
            'code.required' => 'El código del concepto es obligatorio.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede exceder los 100 caracteres.',
            'code.min' => 'El código debe tener al menos 2 caracteres.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos.',
            'code.unique' => 'Ya existe un concepto financiero con este código.',
            
            'type.required' => 'El tipo de concepto es obligatorio.',
            'type.in' => 'El tipo debe ser ingreso (income) o gasto (expense).',
            
            'category.required' => 'La categoría es obligatoria.',
            'category.string' => 'La categoría debe ser una cadena de texto.',
            'category.max' => 'La categoría no puede exceder los 100 caracteres.',
            'category.min' => 'La categoría debe tener al menos 2 caracteres.',
            'category.in' => 'La categoría seleccionada no es válida.',
            
            'school_id.integer' => 'El ID de la escuela debe ser un número entero.',
            'school_id.min' => 'El ID de la escuela debe ser mayor a 0.',
            
            'template_id.integer' => 'El ID del template debe ser un número entero.',
            'template_id.exists' => 'El template seleccionado no existe.',
            
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.'
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
            'school_id' => 'ID de escuela',
            'template_id' => 'ID de template',
            'is_active' => 'estado activo'
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

        // Establecer valores por defecto
        $this->merge([
            'is_active' => $this->boolean('is_active', true)
        ]);
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
                    $validator->errors()->add('category', 'La categoría seleccionada no es válida para conceptos de ingreso.');
                }
                
                if ($type === 'expense' && !in_array($category, $expenseCategories)) {
                    $validator->errors()->add('category', 'La categoría seleccionada no es válida para conceptos de gasto.');
                }
            }

            // Validación personalizada: verificar que el template sea compatible con el tipo
            if ($this->has('template_id') && $this->has('type')) {
                $templateId = $this->template_id;
                $type = $this->type;
                
                if ($templateId) {
                    $template = \App\Models\ConceptTemplate::find($templateId);
                    if ($template && $template->type !== $type) {
                        $validator->errors()->add('template_id', 'El template seleccionado no es compatible con el tipo de concepto.');
                    }
                }
            }
        });
    }
}
