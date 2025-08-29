<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
            'status' => 'required|in:present,absent,late,excused',
            'arrival_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado de asistencia es obligatorio.',
            'status.in' => 'El estado debe ser: presente, ausente, tarde o justificado.',
            'arrival_time.date_format' => 'La hora de llegada debe tener el formato HH:MM.',
            'notes.max' => 'Las notas no pueden exceder los 500 caracteres.'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado de asistencia',
            'arrival_time' => 'hora de llegada',
            'notes' => 'notas'
        ];
    }
}