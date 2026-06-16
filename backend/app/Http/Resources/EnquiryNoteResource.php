<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'enquiryId'     => $this->enquiry_id,
            'content'       => $this->content,
            'createdBy'     => $this->created_by,
            'createdByName' => $this->created_by_name,
            'createdAt'     => $this->created_at?->toISOString(),
        ];
    }
}
