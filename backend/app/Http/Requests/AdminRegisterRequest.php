<?php

namespace App\Http\Requests;

use App\Support\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'phone'    => ['required', 'string', 'max:15'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role'     => ['required', new Enum(Role::class), 'not_in:' . Role::Admin->value . ',' . Role::Customer->value],
        ];
    }
}
