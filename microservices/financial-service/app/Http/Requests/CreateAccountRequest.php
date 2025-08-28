<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountRequest extends FormRequest
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
            'account_number' => 'required|string|max:50|unique:accounts,account_number',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'balance' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
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
            'account_number.required' => 'El número de cuenta es requerido.',
            'account_number.max' => 'El número de cuenta no puede exceder 50 caracteres.',
            'account_number.unique' => 'El número de cuenta ya existe.',
            'type.required' => 'El tipo de cuenta es requerido.',
            'type.in' => 'El tipo debe ser: asset, liability, equity, revenue o expense.',
            'balance.required' => 'El saldo inicial es requerido.',
            'balance.min' => 'El saldo no puede ser negativo.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'created_by.required' => 'El usuario creador es requerido.'
        ];
    }
}
