<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'enquiry_id'       => $this->enquiry_id,
            'enquiry_number'   => $this->whenLoaded('enquiry', fn() => $this->enquiry->enquiry_number),
            'customer'         => $this->whenLoaded('enquiry', fn() => $this->enquiry->customer ? [
                'id'    => $this->enquiry->customer->id,
                'name'  => $this->enquiry->customer->name,
                'phone' => $this->enquiry->customer->phone,
            ] : null),
            'service'          => $this->whenLoaded('enquiry', fn() => $this->enquiry->service ? [
                'id'   => $this->enquiry->service->id,
                'name' => $this->enquiry->service->name,
            ] : null),
            'status'           => $this->status->value,
            'delay_reason'     => $this->delay_reason,
            'progress_percent' => $this->progress_percent,
            'total_units'      => $this->total_units,
            'units_completed'  => $this->units_completed,
            'team'             => $this->whenLoaded('assignments', fn() => ProjectAssignmentResource::collection($this->assignments)),
            'timeline'         => $this->whenLoaded('timeline', fn() => $this->timeline->map(fn($entry) => [
                'status'      => $entry->status,
                'description' => $entry->description,
                'actor_name'  => $entry->actor_name,
                'created_at'  => $entry->created_at?->toIso8601String(),
            ])),
            'final_completion_date' => $this->final_completion_date?->toDateString(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
