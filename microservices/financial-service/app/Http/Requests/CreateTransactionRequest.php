<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
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
            'financial_concept_id' => 'required|integer|exists:financial_concepts,id',
            'reference_number' => 'nullable|string|max:100|unique:transactions,reference_number',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'status' => 'required|in:pending,completed,cancelled,failed',
            'payment_method' => 'nullable|in:cash,bank_transfer,credit_card,debit_card,check,other',
            'metadata' => 'nullable|array',
            'created_by' => 'required|integer|min:1',
            'accounts' => 'required|array|min:1',
            'accounts.*.account_id' => 'required|integer|exists:accounts,id',
            'accounts.*.type' => 'required|in:debit,credit',
            'accounts.*.amount' => 'required|numeric|min:0.01'
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
            'financial_concept_id.required' => 'El concepto financiero es requerido.',
            'financial_concept_id.exists' => 'El concepto financiero seleccionado no existe.',
            'description.required' => 'La descripción es requerida.',
            'amount.required' => 'El monto es requerido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'transaction_date.required' => 'La fecha de transacción es requerida.',
            'status.required' => 'El estado es requerido.',
            'status.in' => 'El estado debe ser: pending, completed, cancelled o failed.',
            'created_by.required' => 'El usuario creador es requerido.',
            'accounts.required' => 'Debe especificar al menos una cuenta.',
            'accounts.*.account_id.required' => 'El ID de cuenta es requerido.',
            'accounts.*.account_id.exists' => 'La cuenta especificada no existe.',
            'accounts.*.type.required' => 'El tipo de movimiento es requerido.',
            'accounts.*.type.in' => 'El tipo debe ser debit o credit.',
            'accounts.*.amount.required' => 'El monto por cuenta es requerido.',
            'accounts.*.amount.min' => 'El monto por cuenta debe ser mayor a 0.'
        ];
    }
}
