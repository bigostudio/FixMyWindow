<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use App\Support\Enums\MaterialType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ServiceCategory::factory(),
            'name'        => fake()->randomElement(['Window Installation', 'Emergency Repair', 'Glass Replacement', 'Facade Fitting']),
            'description' => fake()->sentence(),
            'material'    => MaterialType::Upvc,
            'is_active'   => true,
        ];
    }
}
