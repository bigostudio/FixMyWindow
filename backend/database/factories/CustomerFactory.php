<?php

namespace Database\Factories;

use App\Support\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '9' . fake()->unique()->numerify('#########'),
            'name'  => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'type'  => CustomerType::B2C,
        ];
    }
}
