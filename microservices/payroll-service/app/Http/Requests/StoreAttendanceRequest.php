<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreAttendanceRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('attendances')->where(function ($query) {
                    return $query->where('date', $this->input('date'));
                })
            ],
            'date' => 'required|date|before_or_equal:today',
            'check_in' => 'required|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s|after:check_in',
            'break_start' => 'nullable|date_format:H:i:s|after:check_in',
            'break_end' => 'nullable|date_format:H:i:s|after:break_start|before:check_out',
            'worked_hours' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:12',
            'break_hours' => 'nullable|numeric|min:0|max:8',
            'status' => 'sometimes|in:present,absent,late,half_day,holiday,sick_leave',
            'notes' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:200',
            'ip_address' => 'nullable|ip',
            'device_info' => 'nullable|string|max:300'
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
                    $validator->errors()->add('employee_id', 'El empleado debe estar activo para registrar asistencia.');
                }
            }

            // Validate time logic
            if ($this->has('check_in') && $this->has('check_out')) {
                $checkIn = Carbon::createFromFormat('H:i:s', $this->input('check_in'));
                $checkOut = Carbon::createFromFormat('H:i:s', $this->input('check_out'));
                
                if ($checkOut->lte($checkIn)) {
                    $validator->errors()->add('check_out', 'La hora de salida debe ser posterior a la hora de entrada.');
                }

                // Calculate worked hours if not provided
                if (!$this->has('worked_hours')) {
                    $workedMinutes = $checkOut->diffInMinutes($checkIn);
                    $breakMinutes = 0;
                    
                    if ($this->has('break_start') && $this->has('break_end')) {
                        $breakStart = Carbon::createFromFormat('H:i:s', $this->input('break_start'));
                        $breakEnd = Carbon::createFromFormat('H:i:s', $this->input('break_end'));
                        $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                    }
                    
                    $totalWorkedHours = ($workedMinutes - $breakMinutes) / 60;
                    $this->merge(['worked_hours' => round($totalWorkedHours, 2)]);
                }
            }

            // Validate break times
            if ($this->has('break_start') && $this->has('break_end')) {
                $breakStart = Carbon::createFromFormat('H:i:s', $this->input('break_start'));
                $breakEnd = Carbon::createFromFormat('H:i:s', $this->input('break_end'));
                
                if ($breakEnd->lte($breakStart)) {
                    $validator->errors()->add('break_end', 'La hora de fin de descanso debe ser posterior al inicio.');
                }

                // Calculate break hours if not provided
                if (!$this->has('break_hours')) {
                    $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                    $this->merge(['break_hours' => round($breakMinutes / 60, 2)]);
                }
            }

            // Validate reasonable working hours
            if ($this->has('worked_hours') && $this->input('worked_hours') > 16) {
                $validator->errors()->add('worked_hours', 'Las horas trabajadas no pueden exceder 16 horas por día.');
            }

            // Validate date is not in the future
            if ($this->has('date')) {
                $date = Carbon::parse($this->input('date'));
                if ($date->isFuture()) {
                    $validator->errors()->add('date', 'No se puede registrar asistencia para fechas futuras.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'present']);
        }

        // Set current date if not provided
        if (!$this->has('date')) {
            $this->merge(['date' => now()->format('Y-m-d')]);
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
            'employee_id.unique' => 'Ya existe un registro de asistencia para este empleado en la fecha seleccionada.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.before_or_equal' => 'La fecha no puede ser futura.',
            'check_in.required' => 'La hora de entrada es obligatoria.',
            'check_in.date_format' => 'La hora de entrada debe tener el formato HH:MM:SS.',
            'check_out.date_format' => 'La hora de salida debe tener el formato HH:MM:SS.',
            'check_out.after' => 'La hora de salida debe ser posterior a la hora de entrada.',
            'break_start.date_format' => 'La hora de inicio de descanso debe tener el formato HH:MM:SS.',
            'break_start.after' => 'El descanso debe iniciar después de la hora de entrada.',
            'break_end.date_format' => 'La hora de fin de descanso debe tener el formato HH:MM:SS.',
            'break_end.after' => 'El fin de descanso debe ser posterior al inicio.',
            'break_end.before' => 'El descanso debe terminar antes de la hora de salida.',
            'worked_hours.numeric' => 'Las horas trabajadas deben ser un número.',
            'worked_hours.min' => 'Las horas trabajadas no pueden ser negativas.',
            'worked_hours.max' => 'Las horas trabajadas no pueden exceder 24 horas.',
            'overtime_hours.numeric' => 'Las horas extras deben ser un número.',
            'overtime_hours.min' => 'Las horas extras no pueden ser negativas.',
            'overtime_hours.max' => 'Las horas extras no pueden exceder 12 horas.',
            'status.in' => 'El estado debe ser: presente, ausente, tardanza, medio día, feriado o licencia médica.',
            'ip_address.ip' => 'La dirección IP no es válida.'
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
            'date' => 'fecha',
            'check_in' => 'hora de entrada',
            'check_out' => 'hora de salida',
            'break_start' => 'inicio de descanso',
            'break_end' => 'fin de descanso',
            'worked_hours' => 'horas trabajadas',
            'overtime_hours' => 'horas extras',
            'break_hours' => 'horas de descanso',
            'status' => 'estado',
            'notes' => 'notas',
            'location' => 'ubicación',
            'ip_address' => 'dirección IP',
            'device_info' => 'información del dispositivo'
        ];
    }
}
