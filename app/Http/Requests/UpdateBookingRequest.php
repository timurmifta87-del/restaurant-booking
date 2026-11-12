<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['sometimes', 'integer', 'exists:tables,id'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'guests_count' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'exists' => 'Выбранное значение поля «:attribute» не найдено.',
            'date' => 'Поле «:attribute» должно быть датой.',
            'starts_at.after' => 'Дата начала брони должна быть в будущем.',
            'ends_at.after' => 'Время окончания должно быть позже времени начала.',
            'guests_count.min' => 'Количество гостей должно быть не меньше :min.',
            'status.enum' => 'Недопустимый статус брони.',
            'comment.max' => 'Комментарий не должен быть длиннее :max символов.',
        ];
    }

    public function attributes(): array
    {
        return [
            'table_id' => 'столик',
            'starts_at' => 'начало',
            'ends_at' => 'окончание',
            'guests_count' => 'количество гостей',
            'status' => 'статус',
            'comment' => 'комментарий',
        ];
    }
}
