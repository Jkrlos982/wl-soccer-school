<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
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
            'financial_concept_id' => 'sometimes|integer|exists:financial_concepts,id',
            'description' => 'sometimes|nullable|string|max:1000',
            'amount' => 'sometimes|numeric|min:0.01',
            'transaction_date' => 'sometimes|date|before_or_equal:today',
            'payment_method' => 'sometimes|in:cash,bank_transfer,credit_card,debit_card,check,other',
            'metadata' => 'sometimes|nullable|array'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'financial_concept_id.exists' => 'El concepto financiero seleccionado no existe.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'transaction_date.date' => 'La fecha de transacción debe ser una fecha válida.',
            'transaction_date.before_or_equal' => 'La fecha de transacción no puede ser futura.',
            'payment_method.in' => 'El método de pago seleccionado no es válido.'
        ];
    }
}