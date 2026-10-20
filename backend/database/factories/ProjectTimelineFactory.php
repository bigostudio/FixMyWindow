<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Support\Enums\TimelineActorType;
use App\Support\Enums\TimelineStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTimelineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enquiry_id'  => Enquiry::factory(),
            'status'      => TimelineStatus::New->value,
            'description' => 'Booking created by customer.',
            'actor_type'  => TimelineActorType::Customer,
            'actor_id'    => null,
            'actor_name'  => fake()->name(),
            'created_at'  => now(),
        ];
    }
}
