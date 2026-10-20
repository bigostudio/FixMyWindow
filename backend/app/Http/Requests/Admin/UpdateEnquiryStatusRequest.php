<?php

namespace App\Http\Requests\Admin;

use App\Support\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(
                array_column(
                    array_filter(ProjectStatus::cases(), fn($s) => $s !== ProjectStatus::Draft),
                    'value'
                )
            )],
        ];
    }
}
