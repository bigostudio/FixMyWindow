<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'phone'             => $this->phone,
            'email'             => $this->email,
            'type'              => $this->type?->value,
            'organisation_name' => $this->organisation_name,
            'gst_number'        => $this->gst_number,
            'created_at'        => $this->created_at?->toISOString(),
            'bookings_count'    => $this->enquiries_count,
            'bookings'          => CustomerBookingResource::collection($this->whenLoaded('enquiries')),
        ];
    }
}
