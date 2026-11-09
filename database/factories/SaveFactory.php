<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw rows for tests. Real saves go through App\Actions\Cookbook, which also updates counters.
 *
 * @extends Factory<Save>
 */
class SaveFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory()->public(),
        ];
    }
}
