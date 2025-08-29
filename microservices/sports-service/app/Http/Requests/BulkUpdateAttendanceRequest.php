<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateAttendanceRequest extends FormRequest
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
            'attendances' => 'required|array|min:1',
            'attendances.*.id' => 'required|exists:attendances,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.arrival_time' => 'nullable|date_format:H:i',
            'attendances.*.notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'attendances.required' => 'La lista de asistencias es obligatoria.',
            'attendances.array' => 'Las asistencias deben ser un arreglo.',
            'attendances.min' => 'Debe proporcionar al menos una asistencia.',
            'attendances.*.id.required' => 'El ID de asistencia es obligatorio.',
            'attendances.*.id.exists' => 'La asistencia especificada no existe.',
            'attendances.*.status.required' => 'El estado de asistencia es obligatorio.',
            'attendances.*.status.in' => 'El estado debe ser: presente, ausente, tarde o justificado.',
            'attendances.*.arrival_time.date_format' => 'La hora de llegada debe tener el formato HH:MM.',
            'attendances.*.notes.max' => 'Las notas no pueden exceder los 500 caracteres.'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'attendances' => 'asistencias',
            'attendances.*.id' => 'ID de asistencia',
            'attendances.*.status' => 'estado de asistencia',
            'attendances.*.arrival_time' => 'hora de llegada',
            'attendances.*.notes' => 'notas'
        ];
    }
}