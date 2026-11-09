<?php

namespace App\Actions;

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use App\Notifications\RecipeRated;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Saving, unsaving and rating. A rating needs a save, so unsaving also deletes the member's
 * rating. Each change locks the recipe row, so concurrent saves can't skew the counters.
 * Callers authorize first (RecipePolicy::save and ::rate).
 */
final class Cookbook
{
    public function save(User $user, Recipe $recipe): void
    {
        DB::transaction(function () use ($user, $recipe) {
            $this->lock($recipe);

            Save::query()->firstOrCreate(['user_id' => $user->id, 'recipe_id' => $recipe->id]);

            $recipe->refreshCounters();
        });
    }

    /**
     * Removes the recipe from the cookbook, and the member's rating with it.
     */
    public function remove(User $user, Recipe $recipe): void
    {
        DB::transaction(function () use ($user, $recipe) {
            $this->lock($recipe);

            Save::query()->whereBelongsTo($user)->whereBelongsTo($recipe)->delete();
            Rating::query()->whereBelongsTo($user)->whereBelongsTo($recipe)->delete();

            $recipe->refreshCounters();
        });
    }

    /**
     * Adds or changes the member's rating. The owner hears about the first one only.
     */
    public function rate(User $user, Recipe $recipe, int $stars): void
    {
        if ($stars < 1 || $stars > 5) {
            throw new InvalidArgumentException('A rating is 1 to 5 stars.');
        }

        DB::transaction(function () use ($user, $recipe, $stars) {
            $this->lock($recipe);

            $rating = Rating::query()->updateOrCreate(
                ['user_id' => $user->id, 'recipe_id' => $recipe->id],
                ['stars' => $stars],
            );

            $recipe->refreshCounters();

            if ($rating->wasRecentlyCreated) {
                $recipe->user->notify(new RecipeRated($user, $recipe, $stars));
            }
        });
    }

    private function lock(Recipe $recipe): void
    {
        Recipe::query()->whereKey($recipe->getKey())->lockForUpdate()->value('id');
    }
}
