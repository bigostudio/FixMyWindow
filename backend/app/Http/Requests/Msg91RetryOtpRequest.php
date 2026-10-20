<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Msg91RetryOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'retryChannel' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid 10-digit Indian mobile number.',
        ];
    }
}
