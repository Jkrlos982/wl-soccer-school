<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UpdateLeaveRequestRequest extends FormRequest
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
        $leaveRequestId = $this->route('leave_request');
        
        return [
            'employee_id' => 'sometimes|exists:employees,id',
            'leave_type' => 'sometimes|in:vacation,sick,personal,maternity,paternity,bereavement,emergency,unpaid',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'days_requested' => 'sometimes|numeric|min:0.5|max:365',
            'reason' => 'sometimes|string|min:10|max:1000',
            'medical_certificate' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:200',
            'replacement_employee_id' => 'nullable|exists:employees,id',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'half_day' => 'sometimes|boolean',
            'half_day_period' => 'nullable|in:morning,afternoon|required_if:half_day,true',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'string|max:500',
            'notes' => 'nullable|string|max:500',
            'approved_by' => 'nullable|exists:employees,id',
            'approved_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string|max:500|required_if:status,rejected'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $leaveRequestId = $this->route('leave_request');
            $leaveRequest = \App\Models\LeaveRequest::find($leaveRequestId);
            
            if (!$leaveRequest) {
                $validator->errors()->add('id', 'La solicitud de permiso no existe.');
                return;
            }

            // Validate that employee is active
            if ($this->has('employee_id')) {
                $employee = \App\Models\Employee::find($this->input('employee_id'));
                if ($employee && $employee->status !== 'active') {
                    $validator->errors()->add('employee_id', 'El empleado debe estar activo para solicitar permisos.');
                }
            }

            // Validate status transitions
            if ($this->has('status')) {
                $currentStatus = $leaveRequest->status;
                $newStatus = $this->input('status');
                
                $validTransitions = [
                    'pending' => ['approved', 'rejected', 'cancelled'],
                    'approved' => ['cancelled'],
                    'rejected' => ['pending'], // Allow resubmission
                    'cancelled' => [] // Cannot change from cancelled
                ];
                
                if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
                    $validator->errors()->add('status', "No se puede cambiar el estado de '{$currentStatus}' a '{$newStatus}'.");
                }
                
                // Validate approval requirements
                if ($newStatus === 'approved') {
                    if (!$this->has('approved_by')) {
                        $validator->errors()->add('approved_by', 'Se requiere especificar quién aprueba la solicitud.');
                    }
                    if (!$this->has('approved_at')) {
                        $this->merge(['approved_at' => now()]);
                    }
                }
                
                // Validate rejection requirements
                if ($newStatus === 'rejected' && !$this->has('rejection_reason')) {
                    $validator->errors()->add('rejection_reason', 'Se requiere especificar la razón del rechazo.');
                }
            }

            // Validate date modifications only for pending requests
            if (($this->has('start_date') || $this->has('end_date')) && $leaveRequest->status !== 'pending') {
                $validator->errors()->add('start_date', 'Solo se pueden modificar las fechas de solicitudes pendientes.');
            }

            // Validate date range
            $startDate = $this->input('start_date') ? Carbon::parse($this->input('start_date')) : Carbon::parse($leaveRequest->start_date);
            $endDate = $this->input('end_date') ? Carbon::parse($this->input('end_date')) : Carbon::parse($leaveRequest->end_date);
            
            if ($this->has('start_date') || $this->has('end_date')) {
                // Calculate actual days requested
                $actualDays = $startDate->diffInDays($endDate) + 1;
                
                // For half day requests, adjust the calculation
                if ($this->input('half_day', $leaveRequest->half_day)) {
                    $actualDays = 0.5;
                    
                    // Half day requests should only be for single day
                    if (!$startDate->isSameDay($endDate)) {
                        $validator->errors()->add('end_date', 'Las solicitudes de medio día deben ser para un solo día.');
                    }
                }
                
                // Validate days_requested matches calculated days
                if ($this->has('days_requested') && $this->input('days_requested') != $actualDays) {
                    $validator->errors()->add('days_requested', "Los días solicitados ({$this->input('days_requested')}) no coinciden con el rango de fechas ({$actualDays} días).");
                } elseif (!$this->has('days_requested')) {
                    $this->merge(['days_requested' => $actualDays]);
                }
                
                // Validate maximum leave duration based on type
                $leaveType = $this->input('leave_type', $leaveRequest->leave_type);
                $maxDays = $this->getMaxDaysForLeaveType($leaveType);
                if ($actualDays > $maxDays) {
                    $validator->errors()->add('days_requested', "El tipo de permiso '{$leaveType}' no puede exceder {$maxDays} días.");
                }
                
                // Check for overlapping leave requests (excluding current request)
                $employeeId = $this->input('employee_id', $leaveRequest->employee_id);
                $overlapping = \App\Models\LeaveRequest::where('employee_id', $employeeId)
                    ->where('id', '!=', $leaveRequestId)
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
                
                // Validate that dates are not in the past (only for pending requests)
                if ($leaveRequest->status === 'pending' && $startDate->isPast()) {
                    $validator->errors()->add('start_date', 'No se pueden modificar fechas pasadas.');
                }
            }

            // Validate medical certificate for sick leave
            $leaveType = $this->input('leave_type', $leaveRequest->leave_type);
            $daysRequested = $this->input('days_requested', $leaveRequest->days_requested);
            
            if ($leaveType === 'sick' && $daysRequested > 3 && !$this->has('medical_certificate') && !$leaveRequest->medical_certificate) {
                $validator->errors()->add('medical_certificate', 'Se requiere certificado médico para licencias médicas de más de 3 días.');
            }

            // Validate replacement employee
            if ($this->has('replacement_employee_id')) {
                $replacement = \App\Models\Employee::find($this->input('replacement_employee_id'));
                if ($replacement) {
                    if ($replacement->status !== 'active') {
                        $validator->errors()->add('replacement_employee_id', 'El empleado de reemplazo debe estar activo.');
                    }
                    
                    $employeeId = $this->input('employee_id', $leaveRequest->employee_id);
                    if ($replacement->id === $employeeId) {
                        $validator->errors()->add('replacement_employee_id', 'El empleado de reemplazo no puede ser el mismo solicitante.');
                    }
                }
            }

            // Validate that approved requests cannot be modified (except for cancellation)
            if ($leaveRequest->status === 'approved' && $this->input('status') !== 'cancelled') {
                $modifiableFields = ['status', 'notes', 'approved_by', 'approved_at'];
                $requestFields = array_keys($this->all());
                $nonModifiableFields = array_diff($requestFields, $modifiableFields);
                
                if (!empty($nonModifiableFields)) {
                    $validator->errors()->add('status', 'Las solicitudes aprobadas solo pueden ser canceladas, no modificadas.');
                }
            }

            // Validate available leave balance for vacation type changes
            if ($this->has('leave_type') || $this->has('days_requested')) {
                $employeeId = $this->input('employee_id', $leaveRequest->employee_id);
                $newLeaveType = $this->input('leave_type', $leaveRequest->leave_type);
                $newDaysRequested = $this->input('days_requested', $leaveRequest->days_requested);
                
                if ($newLeaveType === 'vacation') {
                    $availableDays = $this->getAvailableLeaveBalance($employeeId, $newLeaveType);
                    
                    // Add back the original days if changing from vacation to vacation
                    if ($leaveRequest->leave_type === 'vacation') {
                        $availableDays += $leaveRequest->days_requested;
                    }
                    
                    if ($newDaysRequested > $availableDays) {
                        $validator->errors()->add('days_requested', "Días de vacaciones insuficientes. Disponibles: {$availableDays} días.");
                    }
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
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'leave_type.in' => 'El tipo de permiso debe ser: vacaciones, enfermedad, personal, maternidad, paternidad, duelo, emergencia o sin goce.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'days_requested.numeric' => 'Los días solicitados deben ser un número.',
            'days_requested.min' => 'Debe solicitar al menos 0.5 días.',
            'days_requested.max' => 'No se pueden solicitar más de 365 días.',
            'reason.min' => 'La razón debe tener al menos 10 caracteres.',
            'reason.max' => 'La razón no puede exceder 1000 caracteres.',
            'replacement_employee_id.exists' => 'El empleado de reemplazo seleccionado no existe.',
            'status.in' => 'El estado debe ser: pendiente, aprobado, rechazado o cancelado.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
            'half_day_period.required_if' => 'Debe especificar el período (mañana/tarde) para solicitudes de medio día.',
            'half_day_period.in' => 'El período debe ser mañana o tarde.',
            'attachments.max' => 'No se pueden adjuntar más de 5 archivos.',
            'medical_certificate.max' => 'El certificado médico no puede exceder 500 caracteres.',
            'approved_by.exists' => 'El aprobador seleccionado no existe.',
            'approved_at.date' => 'La fecha de aprobación debe ser una fecha válida.',
            'rejection_reason.required_if' => 'Se requiere especificar la razón del rechazo.',
            'rejection_reason.max' => 'La razón del rechazo no puede exceder 500 caracteres.'
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
            'notes' => 'notas',
            'approved_by' => 'aprobado por',
            'approved_at' => 'fecha de aprobación',
            'rejection_reason' => 'razón del rechazo'
        ];
    }
}
