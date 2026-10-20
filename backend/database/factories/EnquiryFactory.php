<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Service;
use App\Support\Enums\CustomerType;
use App\Support\Enums\InspectionType;
use App\Support\Enums\MaterialType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enquiry_number'   => 'FMW-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'customer_id'      => Customer::factory(),
            'service_id'       => Service::factory(),
            'type'             => CustomerType::B2C,
            'city'             => fake()->city(),
            'property_type'    => PropertyType::Home,
            'material_type'    => MaterialType::Upvc,
            'inspection_type'  => InspectionType::Expert,
            'status'           => ProjectStatus::New,
            'latitude'         => fake()->latitude(),
            'longitude'        => fake()->longitude(),
            'address'          => fake()->address(),
            'inspection_fee'   => 1000,
            'payment_status'   => PaymentStatus::Paid,
            'payment_amount'   => 1000,
            'paid_at'          => now(),
            'billing_name'     => fake()->name(),
            'billing_phone'    => '9' . fake()->numerify('#########'),
            'billing_email'    => fake()->safeEmail(),
            'billing_address'  => fake()->address(),
            'booking_date'     => now(),
        ];
    }

    public function status(ProjectStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function unpaid(): static
    {
        return $this->state([
            'payment_status' => PaymentStatus::Pending,
            'payment_amount' => null,
            'paid_at'        => null,
        ]);
    }
}
