<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'integer', 'exists:tables,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'guests_count' => ['required', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:500'],
            'dishes' => ['nullable', 'array'],
            'dishes.*.dish_id' => ['required', 'integer', 'distinct', 'exists:dishes,id'],
            'dishes.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'exists' => 'Выбранное значение поля «:attribute» не найдено.',
            'date' => 'Поле «:attribute» должно быть датой.',
            'starts_at.after' => 'Дата начала брони должна быть в будущем.',
            'ends_at.after' => 'Время окончания должно быть позже времени начала.',
            'guests_count.min' => 'Количество гостей должно быть не меньше :min.',
            'comment.max' => 'Комментарий не должен быть длиннее :max символов.',
            'dishes.array' => 'Предзаказ должен быть списком блюд.',
            'distinct' => 'Блюдо в предзаказе указано повторно.',
            'dishes.*.quantity.min' => 'Количество порций должно быть не меньше :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'table_id' => 'столик',
            'starts_at' => 'начало',
            'ends_at' => 'окончание',
            'guests_count' => 'количество гостей',
            'comment' => 'комментарий',
            'dishes.*.dish_id' => 'блюдо',
            'dishes.*.quantity' => 'количество',
        ];
    }
}