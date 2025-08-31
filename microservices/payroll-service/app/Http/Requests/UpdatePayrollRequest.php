<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollRequest extends FormRequest
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
        $payrollId = $this->route('payroll');
        
        return [
            'employee_id' => [
                'sometimes',
                'exists:employees,id',
                Rule::unique('payrolls')->where(function ($query) {
                    return $query->where('period_id', $this->input('period_id'));
                })->ignore($payrollId)
            ],
            'period_id' => 'sometimes|exists:payroll_periods,id',
            'worked_days' => 'sometimes|numeric|min:0|max:31',
            'worked_hours' => 'sometimes|numeric|min:0|max:744',
            'overtime_hours' => 'sometimes|numeric|min:0|max:200',
            'holiday_hours' => 'sometimes|numeric|min:0|max:200',
            'night_hours' => 'sometimes|numeric|min:0|max:200',
            'sunday_hours' => 'sometimes|numeric|min:0|max:200',
            'base_salary' => 'sometimes|numeric|min:0|max:999999999.99',
            'gross_salary' => 'sometimes|numeric|min:0|max:999999999.99',
            'net_salary' => 'sometimes|numeric|min:0|max:999999999.99',
            'total_deductions' => 'sometimes|numeric|min:0|max:999999999.99',
            'total_earnings' => 'sometimes|numeric|min:0|max:999999999.99',
            'status' => 'sometimes|in:draft,calculated,approved,paid,rechazada',
            'notes' => 'sometimes|string|max:1000',
            
            // Payroll details validation
            'details' => 'sometimes|array',
            'details.*.concept_id' => 'required_with:details|exists:payroll_concepts,id',
            'details.*.amount' => 'required_with:details|numeric',
            'details.*.quantity' => 'nullable|numeric|min:0',
            'details.*.rate' => 'nullable|numeric|min:0',
            'details.*.notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get current payroll to check status
            $payroll = \App\Models\Payroll::find($this->route('payroll'));
            
            // Prevent modification of paid or cancelled payrolls
            if ($payroll && in_array($payroll->status, ['paid', 'cancelled'])) {
                $validator->errors()->add('status', 'No se puede modificar una nómina pagada o cancelada.');
            }

            // Validate that employee is active (if changing employee)
            if ($this->has('employee_id')) {
                $employee = \App\Models\Employee::find($this->input('employee_id'));
                if ($employee && $employee->status !== 'active') {
                    $validator->errors()->add('employee_id', 'El empleado debe estar activo para generar nómina.');
                }
            }

            // Validate that period is open (if changing period)
            if ($this->has('period_id')) {
                $period = \App\Models\PayrollPeriod::find($this->input('period_id'));
                if ($period && $period->status === 'closed') {
                    $validator->errors()->add('period_id', 'No se puede asignar nómina a un período cerrado.');
                }
            }

            // Validate worked hours don't exceed reasonable limits
            if ($this->hasAny(['worked_hours', 'overtime_hours', 'holiday_hours', 'night_hours', 'sunday_hours'])) {
                $totalHours = ($this->input('worked_hours', $payroll->worked_hours ?? 0) + 
                              $this->input('overtime_hours', $payroll->overtime_hours ?? 0) + 
                              $this->input('holiday_hours', $payroll->holiday_hours ?? 0) + 
                              $this->input('night_hours', $payroll->night_hours ?? 0) + 
                              $this->input('sunday_hours', $payroll->sunday_hours ?? 0));
                
                if ($totalHours > 744) {
                    $validator->errors()->add('worked_hours', 'El total de horas trabajadas no puede exceder 744 horas por mes.');
                }
            }

            // Validate status transitions
            if ($this->has('status') && $payroll) {
                $currentStatus = $payroll->status;
                $newStatus = $this->input('status');
                
                $validTransitions = [
                    'draft' => ['calculated', 'cancelled'],
                    'calculated' => ['approved', 'draft', 'cancelled'],
                    'approved' => ['paid', 'calculated', 'cancelled'],
                    'paid' => [], // No transitions allowed from paid
                    'cancelled' => ['draft'] // Only back to draft
                ];
                
                if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
                    $validator->errors()->add('status', "No se puede cambiar el estado de '{$currentStatus}' a '{$newStatus}'.");
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'employee_id.unique' => 'Ya existe una nómina para este empleado en el período seleccionado.',
            'period_id.exists' => 'El período de nómina seleccionado no existe.',
            'worked_days.numeric' => 'Los días trabajados deben ser un número.',
            'worked_days.min' => 'Los días trabajados no pueden ser negativos.',
            'worked_days.max' => 'Los días trabajados no pueden exceder 31 días.',
            'worked_hours.numeric' => 'Las horas trabajadas deben ser un número.',
            'worked_hours.min' => 'Las horas trabajadas no pueden ser negativas.',
            'worked_hours.max' => 'Las horas trabajadas no pueden exceder 744 horas.',
            'overtime_hours.numeric' => 'Las horas extras deben ser un número.',
            'overtime_hours.min' => 'Las horas extras no pueden ser negativas.',
            'overtime_hours.max' => 'Las horas extras no pueden exceder 200 horas.',
            'status.in' => 'El estado debe ser: draft, calculated, approved, paid o rechazada.',
            'details.array' => 'Los detalles de nómina deben ser un arreglo.',
            'details.*.concept_id.required_with' => 'El concepto de nómina es obligatorio.',
            'details.*.concept_id.exists' => 'El concepto de nómina seleccionado no existe.',
            'details.*.amount.required_with' => 'El monto es obligatorio.',
            'details.*.amount.numeric' => 'El monto debe ser un número.'
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
            'employee_id' => 'empleado',
            'period_id' => 'período de nómina',
            'worked_days' => 'días trabajados',
            'worked_hours' => 'horas trabajadas',
            'overtime_hours' => 'horas extras',
            'holiday_hours' => 'horas festivas',
            'night_hours' => 'horas nocturnas',
            'sunday_hours' => 'horas dominicales',
            'base_salary' => 'salario base',
            'gross_salary' => 'salario bruto',
            'net_salary' => 'salario neto',
            'total_deductions' => 'total deducciones',
            'total_earnings' => 'total devengos',
            'status' => 'estado',
            'notes' => 'notas',
            'details' => 'detalles de nómina'
        ];
    }
}
