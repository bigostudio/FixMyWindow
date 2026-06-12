<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'enquiry_id'     => $this->enquiry_id,
            'enquiry_number' => $this->whenLoaded('enquiry', fn() => $this->enquiry->enquiry_number),
            'surveyor'       => $this->whenLoaded('surveyor', fn() => $this->surveyor ? [
                'id'   => $this->surveyor->id,
                'name' => $this->surveyor->name,
                'role' => $this->surveyor->role?->value,
            ] : null),
            'outcome'        => $this->outcome?->value,
            'gonogo_matrix'  => $this->gonogo_matrix,
            'feasibility'    => $this->feasibility,
            'risk_register'  => $this->risk_register,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
