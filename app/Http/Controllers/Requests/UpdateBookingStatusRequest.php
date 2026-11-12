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
            'status' => [
                'required',
                Rule::in([BookingStatus::Confirmed->value, BookingStatus::Cancelled->value]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Укажите новый статус брони.',
            'status.in' => 'Статус может быть только confirmed или cancelled.',
        ];
    }
}