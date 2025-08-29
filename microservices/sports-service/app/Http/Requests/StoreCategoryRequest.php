<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implementar autorización específica
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'min_age' => 'required|integer|min:4|max:25',
            'max_age' => 'required|integer|min:4|max:25|gte:min_age',
            'gender' => 'required|in:male,female,mixed',
            'max_players' => 'required|integer|min:10|max:50',
            'training_days' => 'required|array|min:1',
            'training_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'training_start_time' => 'required|date_format:H:i',
            'training_end_time' => 'required|date_format:H:i|after:training_start_time',
            'field_location' => 'nullable|string|max:200',
            'coach_id' => 'nullable|exists:users,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'min_age.required' => 'La edad mínima es obligatoria.',
            'min_age.min' => 'La edad mínima debe ser al menos 4 años.',
            'min_age.max' => 'La edad mínima no puede ser mayor a 25 años.',
            'max_age.required' => 'La edad máxima es obligatoria.',
            'max_age.gte' => 'La edad máxima debe ser mayor o igual a la edad mínima.',
            'gender.required' => 'El género es obligatorio.',
            'gender.in' => 'El género debe ser: masculino, femenino o mixto.',
            'max_players.required' => 'El número máximo de jugadores es obligatorio.',
            'max_players.min' => 'Debe permitir al menos 10 jugadores.',
            'max_players.max' => 'No puede exceder los 50 jugadores.',
            'training_days.required' => 'Los días de entrenamiento son obligatorios.',
            'training_days.min' => 'Debe seleccionar al menos un día de entrenamiento.',
            'training_days.*.in' => 'Día de entrenamiento inválido.',
            'training_start_time.required' => 'La hora de inicio es obligatoria.',
            'training_start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'training_end_time.required' => 'La hora de fin es obligatoria.',
            'training_end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'training_end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'field_location.max' => 'La ubicación del campo no puede exceder los 200 caracteres.',
            'coach_id.exists' => 'El entrenador seleccionado no existe.'
        ];
    }
}