<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class B2BRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'organisation_name' => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'email'             => ['required', 'email', 'max:255'],
            'password'          => ['required', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid 10-digit Indian mobile number.',
        ];
    }
}
