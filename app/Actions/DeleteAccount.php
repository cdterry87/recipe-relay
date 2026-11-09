<?php

namespace App\Actions;

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use App\Support\Avatars;
use App\Support\RecipeImages;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a member and everything that is theirs: recipes (with their photos), saves,
 * ratings, follows and notifications. Recipes they saved or rated get fresh counters.
 */
final class DeleteAccount
{
    public function handle(User $user): void
    {
        $touched = Save::query()->whereBelongsTo($user)->pluck('recipe_id')
            ->merge(Rating::query()->whereBelongsTo($user)->pluck('recipe_id'))
            ->unique();
        $images = $user->recipes()->whereNotNull('image_path')->pluck('image_path');
        $avatar = $user->avatar_path;

        DB::transaction(function () use ($user, $touched) {
            $user->notifications()->delete();
            $user->delete();

            Recipe::query()->whereIn('id', $touched)->each(fn (Recipe $recipe) => $recipe->refreshCounters());
        });

        Avatars::delete($avatar);
        $images->each(fn (string $path) => RecipeImages::delete($path));
    }
}
