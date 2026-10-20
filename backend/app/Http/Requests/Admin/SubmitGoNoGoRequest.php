<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubmitGoNoGoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string', 'in:go,hold,no_go'],
        ];
    }

    public function messages(): array
    {
        return [
            'outcome.in' => 'Outcome must be one of: go, hold, no_go.',
        ];
    }
}
