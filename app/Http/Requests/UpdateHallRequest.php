<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('halls', 'name')->ignore($this->route('hall')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max.string' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'name.unique' => 'Зал с таким названием уже существует.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'description' => 'описание',
            'is_active' => 'активность',
        ];
    }
}
