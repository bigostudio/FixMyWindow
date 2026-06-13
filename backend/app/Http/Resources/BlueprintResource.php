<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'societyName'        => $this->society_name,
            'towerCount'         => $this->tower_count,
            'floorsPerTower'     => $this->floors_per_tower,
            'flatsPerFloor'      => $this->flats_per_floor,
            'includeGroundFloor' => $this->include_ground_floor,
            'parkingFloors'      => $this->parking_floors,
            'officeFloors'       => $this->office_floors,
            'towers'             => $this->towers,
            'enquiry'            => $this->whenLoaded('enquiry', fn () => [
                'id'             => $this->enquiry->id,
                'enquiry_number' => $this->enquiry->enquiry_number,
            ]),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
