<?php

namespace App\Http\Requests;

use App\Support\Enums\InspectionType;
use App\Support\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth:api middleware already enforces authentication
    }

    public function rules(): array
    {
        return [
            'service_id'              => ['required', 'integer', 'exists:services,id'],
            'property_type'           => ['required', Rule::enum(PropertyType::class)],
            'inspection_type'         => ['required', Rule::enum(InspectionType::class)],

            'location'                => ['required', 'array'],
            'location.latitude'       => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'      => ['required', 'numeric', 'between:-180,180'],
            'location.address'        => ['required', 'string', 'max:500'],
            'location.city'           => ['required', 'string', 'max:100'],

            'billing'                 => ['required', 'array'],
            'billing.name'            => ['required', 'string', 'max:150'],
            'billing.point_of_contact'=> ['nullable', 'string', 'max:150'],
            'billing.gst_number'      => ['nullable', 'string', 'max:20'],
            'billing.phone'           => ['required', 'string', 'digits_between:10,15'],
            'billing.email'           => ['required', 'email', 'max:190'],
            'billing.address'         => ['required', 'string', 'max:500'],
        ];
    }
}
