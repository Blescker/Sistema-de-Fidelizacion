<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['username' => mb_strtolower(trim((string) $this->input('username')))]);
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ];
    }
}
