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
            'financial_concept_id' => 'required|integer|exists:financial_concepts,id',
            'reference_number' => 'nullable|string|max:100|unique:transactions,reference_number',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,debit_card,check,other',
            'metadata' => 'nullable|array',
            'accounts' => 'nullable|array',
            'accounts.*.account_id' => 'required_with:accounts|integer|exists:accounts,id',
            'accounts.*.type' => 'required_with:accounts|in:debit,credit',
            'accounts.*.amount' => 'required_with:accounts|numeric|min:0.01'
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
            'financial_concept_id.required' => 'El concepto financiero es requerido.',
            'financial_concept_id.exists' => 'El concepto financiero seleccionado no existe.',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'transaction_date.required' => 'La fecha de transacción es requerida.',
            'transaction_date.date' => 'La fecha de transacción debe ser una fecha válida.',
            'transaction_date.before_or_equal' => 'La fecha de transacción no puede ser futura.',
            'payment_method.required' => 'El método de pago es requerido.',
            'payment_method.in' => 'El método de pago seleccionado no es válido.',
            'reference_number.unique' => 'El número de referencia ya existe.',
            'accounts.*.account_id.required_with' => 'El ID de cuenta es requerido cuando se especifican cuentas.',
            'accounts.*.account_id.exists' => 'La cuenta especificada no existe.',
            'accounts.*.type.required_with' => 'El tipo de movimiento es requerido.',
            'accounts.*.type.in' => 'El tipo debe ser debit o credit.',
            'accounts.*.amount.required_with' => 'El monto por cuenta es requerido.',
            'accounts.*.amount.min' => 'El monto por cuenta debe ser mayor a 0.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that accounts belong to the same school
            if ($this->has('accounts') && is_array($this->accounts)) {
                $schoolId = $this->header('X-School-ID');
                if ($schoolId) {
                    foreach ($this->accounts as $index => $account) {
                        if (isset($account['account_id'])) {
                            $accountModel = \App\Models\Account::find($account['account_id']);
                            if ($accountModel && $accountModel->school_id != $schoolId) {
                                $validator->errors()->add(
                                    "accounts.{$index}.account_id",
                                    'La cuenta no pertenece a la escuela actual.'
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
