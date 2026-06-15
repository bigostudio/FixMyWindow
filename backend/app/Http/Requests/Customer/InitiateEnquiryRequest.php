<?php

namespace App\Http\Requests\Customer;

use App\Support\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id'               => ['required', 'integer', 'exists:services,id'],
            'property_type'            => ['required', Rule::enum(PropertyType::class)],

            'location'                 => ['required', 'array'],
            'location.latitude'        => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'       => ['required', 'numeric', 'between:-180,180'],
            'location.address'         => ['required', 'string', 'max:500'],
            'location.city'            => ['required', 'string', 'max:100'],

            'billing'                  => ['required', 'array'],
            'billing.name'             => ['required', 'string', 'max:150'],
            'billing.point_of_contact' => ['nullable', 'string', 'max:150'],
            'billing.gst_number'       => ['nullable', 'string', 'max:20'],
            'billing.phone'            => ['required', 'string', 'digits_between:10,15'],
            'billing.email'            => ['nullable', 'email', 'max:190'],
            'billing.address'          => ['required', 'string', 'max:500'],
        ];
    }
}
