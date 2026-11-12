<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuCategoryRequest extends FormRequest
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
                Rule::unique('menu_categories', 'name')->ignore($this->route('menu_category')),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max.string' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'name.unique' => 'Категория с таким названием уже существует.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'min' => 'Поле «:attribute» должно быть не меньше :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'sort_order' => 'порядок',
        ];
    }
}