<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlueprintPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'enquiryId'      => $this->enquiry_id,
            'url'            => Storage::disk('cloud')->url($this->file_path),
            'originalName'   => $this->original_name,
            'uploadedByType' => $this->uploaded_by_type,
            'uploadedById'   => $this->uploaded_by_id,
            'createdAt'      => $this->created_at?->toISOString(),
        ];
    }
}
