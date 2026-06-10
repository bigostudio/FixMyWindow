<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_id'          => $this->enquiry_number,
            'customer_name'       => $this->customer?->name,
            'service_type'        => $this->service?->name,
            'status'              => $this->status->value,
            'priority'            => null,
            'operations_manager'  => 'Not assigned',
            'supervisor'          => 'Not assigned',
            'delivery'            => null,
            'created_date'        => $this->created_at?->toDateString(),
        ];
    }
}
