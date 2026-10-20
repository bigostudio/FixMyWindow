<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffWithStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'role'            => $this->role?->value,
            'active_projects' => (int) ($this->active_projects ?? 0),
            'completed'       => (int) ($this->completed ?? 0),
            'enquiry_numbers' => $this->enquiry_numbers ?? [],
        ];
    }
}
