<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'phone'      => $this->phone,
            'name'       => $this->name,
            'email'      => $this->email,
            'type'       => $this->type?->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
