<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'dish_id.exists' => 'Выбранное блюдо не найдено.',
            'quantity.min' => 'Количество порций должно быть не меньше :min.',
            'quantity.max' => 'Количество порций не должно быть больше :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'dish_id' => 'блюдо',
            'quantity' => 'количество',
        ];
    }
}