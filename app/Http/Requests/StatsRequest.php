<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'Дата «с» указана неверно.',
            'date_to.date' => 'Дата «по» указана неверно.',
            'date_to.after_or_equal' => 'Дата «по» не может быть раньше даты «с».',
        ];
    }
}
