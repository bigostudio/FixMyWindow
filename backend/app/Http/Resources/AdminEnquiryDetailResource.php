<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEnquiryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'booking_id'     => $this->enquiry_number,
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'type'           => $this->type->value,

            'customer'       => [
                'id'    => $this->customer?->id,
                'name'  => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'email' => $this->customer?->email,
            ],

            'service'        => [
                'id'       => $this->service?->id,
                'name'     => $this->service?->name,
                'material' => $this->service?->material?->value,
                'category' => $this->service?->category?->name,
            ],

            'property_type'   => $this->property_type->value,
            'material_type'   => $this->material_type->value,
            'inspection_type' => $this->inspection_type->value,
            'inspection_fee'  => $this->inspection_fee,
            'payment_status'  => $this->payment_status->value,
            'payment_amount'  => $this->payment_amount,
            'payment_ref'     => $this->payment_ref,
            'receipt_url'     => $this->receipt_url,

            'location'       => [
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
                'address'   => $this->address,
                'city'      => $this->city,
            ],

            'billing'        => [
                'name'             => $this->billing_name,
                'point_of_contact' => $this->billing_poc,
                'gst_number'       => $this->billing_gst,
                'phone'            => $this->billing_phone,
                'email'            => $this->billing_email,
                'address'          => $this->billing_address,
            ],

            'progress'       => [
                'total_units'      => $this->total_units,
                'units_completed'  => $this->units_completed,
                'progress_percent' => $this->progress_percent,
                'delay_reason'     => $this->delay_reason,
            ],

            'team'           => $this->whenLoaded('assignments',
                fn() => ProjectAssignmentResource::collection($this->assignments)
            ),

            'timeline'       => $this->whenLoaded('timeline',
                fn() => $this->timeline->map(fn($entry) => [
                    'status'      => $entry->status,
                    'description' => $entry->description,
                    'actor_name'  => $entry->actor_name,
                    'created_at'  => $entry->created_at?->toIso8601String(),
                ])
            ),

            'final_completion_date' => $this->final_completion_date?->toDateString(),
            'survey_id'      => $this->survey?->id,
            'project_stage_id' => $this->projectStage?->id,
            'blueprint_id'   => $this->blueprint_id,
            'booking_date'   => $this->booking_date?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
