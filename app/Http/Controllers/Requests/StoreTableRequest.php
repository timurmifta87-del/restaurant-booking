<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'number' => [
                'required', 'integer', 'min:1',
                Rule::unique('tables', 'number')->where('hall_id', $this->input('hall_id')),
            ],
            'seats' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'exists' => 'Выбранный зал не найден.',
            'number.unique' => 'Столик с таким номером уже есть в этом зале.',
            'min' => 'Поле «:attribute» должно быть не меньше :min.',
            'seats.max' => 'Вместимость не может быть больше :max мест.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hall_id' => 'зал',
            'number' => 'номер',
            'seats' => 'вместимость',
            'is_active' => 'активность',
        ];
    }
}