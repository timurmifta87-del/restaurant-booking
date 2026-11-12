<?php

namespace App\Http\Requests;

use App\Models\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = $this->route('table');
        $hallId = $this->input('hall_id', $table instanceof Table ? $table->hall_id : null);

        return [
            'hall_id' => ['sometimes', 'integer', 'exists:halls,id'],
            'number' => [
                'sometimes', 'integer', 'min:1',
                Rule::unique('tables', 'number')->where('hall_id', $hallId)->ignore($table),
            ],
            'seats' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
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
