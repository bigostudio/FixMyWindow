<?php

namespace App\Http\Requests\Customer;

use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;

class GenerateBlueprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = Enquiry::find($this->input('enquiry_id'));
        return $enquiry && $enquiry->customer_id === auth('api')->id();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'society_name'         => $this->input('societyName'),
            'tower_count'          => $this->input('towerCount'),
            'floors_per_tower'     => $this->input('floorsPerTower'),
            'flats_per_floor'      => $this->input('flatsPerFloor'),
            'include_ground_floor' => $this->input('includeGroundFloor'),
            'parking_floors'       => $this->input('parkingFloors'),
            'office_floors'        => $this->input('officeFloors'),
        ]);
    }

    public function rules(): array
    {
        return [
            'enquiry_id'           => ['required', 'integer', 'exists:enquiries,id'],
            'society_name'         => ['nullable', 'string', 'max:190'],
            'tower_count'          => ['required', 'integer', 'min:0'],
            'floors_per_tower'     => ['required', 'integer', 'min:0'],
            'flats_per_floor'      => ['required', 'integer', 'min:0'],
            'include_ground_floor' => ['nullable', 'boolean'],
            'parking_floors'       => ['nullable', 'integer', 'min:0'],
            'office_floors'        => ['nullable', 'integer', 'min:0'],
            'towers'               => ['nullable', 'array'],
        ];
    }
}
