<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'enquiryId'      => $this->enquiry_id,
            'towers'         => $this->towers,
            'approvalStatus' => $this->approval_status,
            'releasedAt'     => $this->released_at?->toISOString(),
            'createdAt'      => $this->created_at?->toISOString(),
            'updatedAt'      => $this->updated_at?->toISOString(),
        ];
    }
}
