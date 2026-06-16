<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'booking_id'       => $this->enquiry_number,
            'service_type'     => $this->service?->name,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'payment_status'   => $this->payment_status->value,
            'progress_percent' => $this->progress_percent,
            'booking_date'     => $this->booking_date?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
