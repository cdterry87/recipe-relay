<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'position' => 1,
            'quantity' => (string) fake()->numberBetween(1, 4),
            'unit' => fake()->randomElement(['cup', 'tbsp', 'tsp', 'g', null]),
            'name' => fake()->word(),
        ];
    }
}
