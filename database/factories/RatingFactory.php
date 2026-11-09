<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw rows for tests. Real ratings go through App\Actions\Cookbook.
 *
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory()->public(),
            'stars' => fake()->numberBetween(1, 5),
        ];
    }
}
