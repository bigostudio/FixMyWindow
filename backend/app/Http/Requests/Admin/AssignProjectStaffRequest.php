<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignProjectStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required'   => 'At least one user ID is required.',
            'user_ids.array'      => 'user_ids must be an array.',
            'user_ids.min'        => 'Provide at least one user ID.',
            'user_ids.*.integer'  => 'Each user ID must be an integer.',
        ];
    }
}
