<?php

namespace App\Http\Requests\Customer;

use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;

class UploadBlueprintPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = Enquiry::find($this->route('id'));

        return $enquiry && $enquiry->customer_id === auth('api')->id();
    }

    public function rules(): array
    {
        return [
            'photos'   => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
