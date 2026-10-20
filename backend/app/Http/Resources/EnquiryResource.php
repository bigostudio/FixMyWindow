<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'booking_id'      => $this->enquiry_number,
            'status'          => $this->status->value,
            'status_label'    => $this->status->label(),
            'type'            => $this->type->value,

            'service'         => [
                'id'       => $this->service?->id,
                'name'     => $this->service?->name,
                'material' => $this->service?->material?->value,
                'category' => $this->service?->category?->name,
            ],

            'property_type'   => $this->property_type->value,
            'material_type'   => $this->material_type->value,
            'inspection_type' => $this->inspection_type->value,
            'inspection_fee'  => $this->inspection_fee,
            'payment_status'  => $this->payment_status->value,

            'progress'        => [
                'total_units'      => $this->total_units,
                'units_completed'  => $this->units_completed,
                'progress_percent' => $this->progress_percent,
            ],

            'location'        => [
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
                'address'   => $this->address,
                'city'      => $this->city,
            ],

            'billing'         => [
                'name'              => $this->billing_name,
                'point_of_contact'  => $this->billing_poc,
                'gst_number'        => $this->billing_gst,
                'phone'             => $this->billing_phone,
                'email'             => $this->billing_email,
                'address'           => $this->billing_address,
            ],

            'booking_date'    => $this->booking_date?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
