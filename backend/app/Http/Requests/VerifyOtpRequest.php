<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'otp'   => ['required', 'string', 'size:6', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid 10-digit Indian mobile number.',
            'otp.size'    => 'OTP must be exactly 6 digits.',
            'otp.digits'  => 'OTP must be numeric.',
        ];
    }
}
