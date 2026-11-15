<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingFilterRequest extends FormRequest
{
    /** Белый список полей сортировки */
    public const SORTABLE = ['starts_at', 'guests_count', 'created_at', 'id'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'hall_id' => ['nullable', 'integer', 'exists:halls,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Неизвестный статус брони.',
            'hall_id.exists' => 'Зал не найден.',
            'date_from.date' => 'Дата «с» указана неверно.',
            'date_to.date' => 'Дата «по» указана неверно.',
            'date_to.after_or_equal' => 'Дата «по» не может быть раньше даты «с».',
            'search.max' => 'Строка поиска не должна быть длиннее :max символов.',
            'sort.in' => 'Сортировать можно по полям: ' . implode(', ', self::SORTABLE) . '.',
            'order.in' => 'Направление сортировки: asc или desc.',
            'per_page.max' => 'На странице может быть не больше :max записей.',
            'per_page.min' => 'На странице должна быть хотя бы :min запись.',
        ];
    }
}
