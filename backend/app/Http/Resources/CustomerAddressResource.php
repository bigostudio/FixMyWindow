<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'address'   => $this->address,
            'city'      => $this->city,
            'latitude'  => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }
}
