<?php

namespace Database\Factories;

use App\Enums\ParseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'yandex_url' => 'https://yandex.kz/maps/org/test/' . fake()->unique()->numberBetween(100000, 999999) . '/reviews/',
            'name' => fake()->company(),
            'rating' => fake()->randomFloat(2, 1, 5),
            'ratings_count' => fake()->numberBetween(1, 1000),
            'reviews_count' => fake()->numberBetween(1, 1000),
            'parse_status' => ParseStatus::Success,
            'parse_error' => null,
            'last_parsed_at' => now(),
        ];
    }
}
