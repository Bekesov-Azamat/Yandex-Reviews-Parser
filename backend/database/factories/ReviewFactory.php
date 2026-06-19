<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'external_id' => fake()->unique()->uuid(),
            'author_name' => fake()->name(),
            'reviewed_at' => now()->subDays(fake()->numberBetween(0, 365)),
            'text' => fake()->paragraph(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
