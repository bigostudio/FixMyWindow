<?php

namespace Database\Factories;

use App\Support\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'phone'     => '9' . fake()->unique()->numerify('#########'),
            'password'  => static::$password ??= Hash::make('password'),
            'role'      => Role::OpsAdmin,
            'is_active' => true,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => Role::SuperAdmin]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
