<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_category_id' => ['sometimes', 'integer', 'exists:menu_categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'numeric', 'min:0.01', 'max:99999999.99'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max.string' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'exists' => 'Выбранная категория не найдена.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена должна быть больше нуля.',
            'image' => 'Файл должен быть изображением.',
            'image.max' => 'Размер изображения не должен превышать 2 МБ.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'menu_category_id' => 'категория',
            'name' => 'название',
            'description' => 'описание',
            'price' => 'цена',
            'image' => 'изображение',
            'is_available' => 'доступность',
        ];
    }
}
