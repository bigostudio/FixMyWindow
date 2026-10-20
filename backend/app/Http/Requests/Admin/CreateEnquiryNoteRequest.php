<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateEnquiryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enquiry_id' => ['required', 'integer', 'exists:enquiries,id'],
            'content'    => ['required', 'string'],
        ];
    }
}
