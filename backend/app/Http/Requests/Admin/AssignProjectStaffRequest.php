<?php

namespace App\Http\Requests\Admin;

use App\Support\Enums\AssignmentSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignProjectStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sections = array_column(AssignmentSection::cases(), 'value');

        $rules = [];
        foreach ($sections as $section) {
            $rules[$section]        = ['nullable', 'array'];
            $rules["{$section}.*"]  = ['integer', 'min:1', Rule::exists('users', 'id')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            '*.array'    => 'Each section must be an array of user IDs.',
            '*.*.integer' => 'Each user ID must be an integer.',
            '*.*.exists'  => 'One or more user IDs do not exist.',
        ];
    }
}
