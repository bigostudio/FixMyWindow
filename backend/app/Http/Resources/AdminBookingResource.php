<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $team = $this->whenLoaded('assignments', fn() =>
            $this->assignments->map(fn($a) => [
                'name' => $a->user?->name,
                'role' => $a->role,
            ])
        );

        return [
            'id'               => $this->id,
            'booking_id'       => $this->enquiry_number,
            'customer_name'    => $this->customer?->name,
            'service_type'     => $this->service?->name,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'progress_percent' => $this->progress_percent,
            'team'             => $team,
            'booking_date'     => $this->booking_date?->toDateString(),
            'created_date'     => $this->created_at?->toDateString(),
        ];
    }
}
