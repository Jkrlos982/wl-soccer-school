<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFinancialConceptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'school_id' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:income,expense,transfer',
            'category' => 'required|string|max:100',
            'is_active' => 'boolean',
            'created_by' => 'required|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'school_id.required' => 'El ID de la escuela es requerido.',
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'type.required' => 'El tipo es requerido.',
            'type.in' => 'El tipo debe ser: income, expense o transfer.',
            'category.required' => 'La categoría es requerida.',
            'category.max' => 'La categoría no puede exceder 100 caracteres.',
            'created_by.required' => 'El usuario creador es requerido.'
        ];
    }
}
