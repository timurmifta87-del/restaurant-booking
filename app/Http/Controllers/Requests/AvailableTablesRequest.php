<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailableTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'guests_count' => ['required', 'integer', 'min:1'],
            'hall_id' => ['nullable', 'integer', 'exists:halls,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'date' => 'Поле «:attribute» должно быть датой.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'starts_at.after' => 'Начало должно быть в будущем.',
            'ends_at.after' => 'Окончание должно быть позже начала.',
            'guests_count.min' => 'Количество гостей должно быть не меньше :min.',
            'hall_id.exists' => 'Выбранный зал не найден.',
        ];
    }

    public function attributes(): array
    {
        return [
            'starts_at' => 'начало',
            'ends_at' => 'окончание',
            'guests_count' => 'количество гостей',
            'hall_id' => 'зал',
        ];
    }
}