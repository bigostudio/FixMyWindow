<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enquiry_id'  => ['required', 'integer', 'exists:enquiries,id'],
            'surveyor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
