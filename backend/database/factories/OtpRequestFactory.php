<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class OtpRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone'           => '9' . fake()->numerify('#########'),
            'otp_hash'        => Hash::make('123456'),
            'expires_at'      => now()->addMinutes(5),
            'consumed_at'     => null,
            'failed_attempts' => 0,
            'locked_until'    => null,
            'created_at'      => now(),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinutes(10)]);
    }

    public function consumed(): static
    {
        return $this->state(['consumed_at' => now()->subMinutes(1)]);
    }
}
