<?php

namespace App\Http\Controllers;

use App\Enums\Visibility;
use App\Models\Recipe;
use App\Support\RecipeSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecipeController
{
    /**
     * The normal recipe page: public recipes, or any recipe for its owner.
     */
    public function show(Recipe $recipe): View
    {
        Gate::authorize('view', $recipe);

        return $this->page($recipe);
    }

    /**
     * The secret share link, which also opens unlisted recipes.
     */
    public function shared(string $token): View
    {
        $recipe = Recipe::query()->where('share_token', $token)->firstOrFail();

        Gate::authorize('viewShared', $recipe);

        return $this->page($recipe);
    }

    private function page(Recipe $recipe): View
    {
        $recipe->load(['user', 'ingredients', 'steps']);

        $public = $recipe->visibility === Visibility::Public;
        $cards = fn () => Recipe::query()->listed()->with('user')->whereKeyNot($recipe->getKey())->latest('published_at')->limit(4);

        return view('recipes.show', [
            'recipe' => $recipe,
            'isPublic' => $public,
            'schema' => $public ? RecipeSchema::for($recipe) : null,
            'fromCreator' => $public ? $cards()->whereBelongsTo($recipe->user)->get() : collect(),
            'alsoLike' => $public && $recipe->meal_type
                ? $cards()->whereNot('user_id', $recipe->user_id)->where('meal_type', $recipe->meal_type)->get()
                : collect(),
        ]);
    }
}
