<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreLeaveRequestRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:vacation,sick,personal,maternity,paternity,bereavement,emergency,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_requested' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|min:10|max:1000',
            'medical_certificate' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:200',
            'replacement_employee_id' => 'nullable|exists:employees,id',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'half_day' => 'sometimes|boolean',
            'half_day_period' => 'nullable|in:morning,afternoon|required_if:half_day,true',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'string|max:500',
            'notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that employee is active
            if ($this->has('employee_id')) {
                $employee = \App\Models\Employee::find($this->input('employee_id'));
                if ($employee && $employee->status !== 'active') {
                    $validator->errors()->add('employee_id', 'El empleado debe estar activo para solicitar permisos.');
                }
            }

            // Validate date range
            if ($this->has('start_date') && $this->has('end_date')) {
                $startDate = Carbon::parse($this->input('start_date'));
                $endDate = Carbon::parse($this->input('end_date'));
                
                // Calculate actual days requested
                $actualDays = $startDate->diffInDays($endDate) + 1;
                
                // For half day requests, adjust the calculation
                if ($this->input('half_day')) {
                    $actualDays = 0.5;
                    
                    // Half day requests should only be for single day
                    if (!$startDate->isSameDay($endDate)) {
                        $validator->errors()->add('end_date', 'Las solicitudes de medio día deben ser para un solo día.');
                    }
                }
                
                // Validate days_requested matches calculated days
                if ($this->has('days_requested') && $this->input('days_requested') != $actualDays) {
                    $validator->errors()->add('days_requested', "Los días solicitados ({$this->input('days_requested')}) no coinciden con el rango de fechas ({$actualDays} días).");
                }
                
                // Validate maximum leave duration based on type
                $maxDays = $this->getMaxDaysForLeaveType($this->input('leave_type'));
                if ($actualDays > $maxDays) {
                    $validator->errors()->add('days_requested', "El tipo de permiso '{$this->input('leave_type')}' no puede exceder {$maxDays} días.");
                }
                
                // Check for overlapping leave requests
                if ($this->has('employee_id')) {
                    $overlapping = \App\Models\LeaveRequest::where('employee_id', $this->input('employee_id'))
                        ->where('status', '!=', 'rejected')
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('start_date', [$startDate, $endDate])
                                  ->orWhereBetween('end_date', [$startDate, $endDate])
                                  ->orWhere(function ($q) use ($startDate, $endDate) {
                                      $q->where('start_date', '<=', $startDate)
                                        ->where('end_date', '>=', $endDate);
                                  });
                        })->exists();
                    
                    if ($overlapping) {
                        $validator->errors()->add('start_date', 'Ya existe una solicitud de permiso para este período.');
                    }
                }
                
                // Validate advance notice requirements
                $daysInAdvance = now()->diffInDays($startDate);
                $requiredAdvance = $this->getRequiredAdvanceNotice($this->input('leave_type'));
                
                if ($daysInAdvance < $requiredAdvance && !in_array($this->input('leave_type'), ['sick', 'emergency'])) {
                    $validator->errors()->add('start_date', "Las solicitudes de '{$this->input('leave_type')}' requieren al menos {$requiredAdvance} días de anticipación.");
                }
            }

            // Validate medical certificate for sick leave
            if ($this->input('leave_type') === 'sick' && $this->input('days_requested') > 3 && !$this->has('medical_certificate')) {
                $validator->errors()->add('medical_certificate', 'Se requiere certificado médico para licencias médicas de más de 3 días.');
            }

            // Validate replacement employee
            if ($this->has('replacement_employee_id')) {
                $replacement = \App\Models\Employee::find($this->input('replacement_employee_id'));
                if ($replacement) {
                    if ($replacement->status !== 'active') {
                        $validator->errors()->add('replacement_employee_id', 'El empleado de reemplazo debe estar activo.');
                    }
                    
                    if ($replacement->id === $this->input('employee_id')) {
                        $validator->errors()->add('replacement_employee_id', 'El empleado de reemplazo no puede ser el mismo solicitante.');
                    }
                }
            }

            // Validate available leave balance
            if ($this->has('employee_id') && $this->has('leave_type') && $this->has('days_requested')) {
                $availableDays = $this->getAvailableLeaveBalance($this->input('employee_id'), $this->input('leave_type'));
                if ($this->input('days_requested') > $availableDays && $this->input('leave_type') === 'vacation') {
                    $validator->errors()->add('days_requested', "Días de vacaciones insuficientes. Disponibles: {$availableDays} días.");
                }
            }
        });
    }

    /**
     * Get maximum allowed days for leave type.
     */
    private function getMaxDaysForLeaveType(string $leaveType): int
    {
        return match($leaveType) {
            'vacation' => 30,
            'sick' => 90,
            'personal' => 5,
            'maternity' => 126, // 18 weeks
            'paternity' => 14,
            'bereavement' => 5,
            'emergency' => 3,
            'unpaid' => 365,
            default => 30
        };
    }

    /**
     * Get required advance notice days for leave type.
     */
    private function getRequiredAdvanceNotice(string $leaveType): int
    {
        return match($leaveType) {
            'vacation' => 15,
            'personal' => 7,
            'maternity' => 30,
            'paternity' => 15,
            'unpaid' => 30,
            'sick', 'emergency', 'bereavement' => 0,
            default => 7
        };
    }

    /**
     * Get available leave balance for employee.
     */
    private function getAvailableLeaveBalance(int $employeeId, string $leaveType): float
    {
        // This would typically query a leave_balances table or calculate based on employee data
        // For now, return a default value
        return match($leaveType) {
            'vacation' => 20, // Default vacation days
            'sick' => 10,     // Default sick days
            'personal' => 5,  // Default personal days
            default => 0
        };
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'pending']);
        }

        // Set default priority if not provided
        if (!$this->has('priority')) {
            $priority = match($this->input('leave_type')) {
                'emergency', 'sick' => 'high',
                'maternity', 'paternity', 'bereavement' => 'urgent',
                default => 'normal'
            };
            $this->merge(['priority' => $priority]);
        }

        // Calculate days_requested if not provided
        if (!$this->has('days_requested') && $this->has('start_date') && $this->has('end_date')) {
            $startDate = Carbon::parse($this->input('start_date'));
            $endDate = Carbon::parse($this->input('end_date'));
            $days = $this->input('half_day') ? 0.5 : $startDate->diffInDays($endDate) + 1;
            $this->merge(['days_requested' => $days]);
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
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'leave_type.required' => 'El tipo de permiso es obligatorio.',
            'leave_type.in' => 'El tipo de permiso debe ser: vacaciones, enfermedad, personal, maternidad, paternidad, duelo, emergencia o sin goce.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'days_requested.required' => 'Los días solicitados son obligatorios.',
            'days_requested.integer' => 'Los días solicitados deben ser un número entero.',
            'days_requested.min' => 'Debe solicitar al menos 1 día.',
            'days_requested.max' => 'No se pueden solicitar más de 365 días.',
            'reason.required' => 'La razón del permiso es obligatoria.',
            'reason.min' => 'La razón debe tener al menos 10 caracteres.',
            'reason.max' => 'La razón no puede exceder 1000 caracteres.',
            'replacement_employee_id.exists' => 'El empleado de reemplazo seleccionado no existe.',
            'status.in' => 'El estado debe ser: pendiente, aprobado, rechazado o cancelado.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
            'half_day_period.required_if' => 'Debe especificar el período (mañana/tarde) para solicitudes de medio día.',
            'half_day_period.in' => 'El período debe ser mañana o tarde.',
            'attachments.max' => 'No se pueden adjuntar más de 5 archivos.',
            'medical_certificate.max' => 'El certificado médico no puede exceder 500 caracteres.'
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
            'leave_type' => 'tipo de permiso',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'days_requested' => 'días solicitados',
            'reason' => 'razón',
            'medical_certificate' => 'certificado médico',
            'emergency_contact' => 'contacto de emergencia',
            'replacement_employee_id' => 'empleado de reemplazo',
            'status' => 'estado',
            'priority' => 'prioridad',
            'half_day' => 'medio día',
            'half_day_period' => 'período del día',
            'attachments' => 'archivos adjuntos',
            'notes' => 'notas'
        ];
    }
}
