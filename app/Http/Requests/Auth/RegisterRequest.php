<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Email указан в неверном формате.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Укажите пароль.',
            'password.min' => 'Пароль должен быть не короче :min символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];
    }
}
