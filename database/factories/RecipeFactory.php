<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'summary' => fake()->sentence(),
            'prep_minutes' => 10,
            'cook_minutes' => 20,
            'servings' => 4,
            'difficulty' => Difficulty::Easy,
            'meal_type' => MealType::Dinner,
        ];
    }

    /**
     * A finished recipe with two ingredients and two steps.
     */
    public function complete(): static
    {
        return $this->state(['status' => RecipeStatus::Complete])
            ->has(Ingredient::factory()->count(2)->sequence(['position' => 1], ['position' => 2]))
            ->has(Step::factory()->count(2)->sequence(['position' => 1], ['position' => 2]));
    }

    public function public(): static
    {
        return $this->complete()->state(['visibility' => Visibility::Public, 'published_at' => now()]);
    }

    public function unlisted(): static
    {
        return $this->complete()->state(['visibility' => Visibility::Unlisted]);
    }
}
