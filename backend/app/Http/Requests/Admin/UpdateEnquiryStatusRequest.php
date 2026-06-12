<?php

namespace App\Http\Requests\Admin;

use App\Support\Enums\EnquiryStatus;
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
            'status' => ['required', 'string', Rule::in([
                EnquiryStatus::Assigned->value,
                EnquiryStatus::InProgress->value,
                EnquiryStatus::Completed->value,
                EnquiryStatus::Cancelled->value,
            ])],
        ];
    }
}
