<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Укажите новый статус брони.',
            'status.enum' => 'Статус может быть: new, confirmed, completed, cancelled.',
        ];
    }
}
