<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccupancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Укажите дату.',
            'date.date_format' => 'Дата должна быть в формате ГГГГ-ММ-ДД.',
        ];
    }
}