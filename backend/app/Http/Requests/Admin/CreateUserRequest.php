<?php

namespace App\Http\Requests\Admin;

use App\Support\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedRoles = array_map(
            fn (Role $r) => $r->value,
            array_filter(
                Role::cases(),
                fn (Role $r) => $r !== Role::Customer
            )
        );

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'phone'    => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'     => ['required', Rule::in($allowedRoles)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be a valid 10-digit Indian mobile number.',
            'role.in'     => 'The selected role is invalid.',
        ];
    }
}
