<?php

namespace App\Policies;

use App\Enums\Visibility;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may see and change recipes. Hidden recipes answer "not found", never "forbidden",
 * so their existence is not revealed.
 */
class RecipePolicy
{
    /**
     * Opening the normal recipe page: public recipes for everyone, anything for its owner.
     */
    public function view(?User $user, Recipe $recipe): Response
    {
        return $recipe->visibility === Visibility::Public || $recipe->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Opening the secret share link: unlisted and public recipes for everyone.
     */
    public function viewShared(?User $user, Recipe $recipe): Response
    {
        return $recipe->visibility->isShared() || $recipe->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Adding to a cookbook: shareable recipes the member doesn't own.
     */
    public function save(User $user, Recipe $recipe): Response
    {
        if ($recipe->isOwnedBy($user)) {
            return Response::deny("You can't save your own recipe. It's already in My recipes.");
        }

        return $recipe->isSaveable() ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Rating: only recipes the member has saved.
     */
    public function rate(User $user, Recipe $recipe): Response
    {
        $canSave = $this->save($user, $recipe);

        if ($canSave->denied()) {
            return $canSave;
        }

        return $user->hasSaved($recipe) ? Response::allow() : Response::deny('Save this recipe to your cookbook to rate it.');
    }

    public function create(User $user): Response
    {
        return $user->recipes()->count() < $user->recipeLimit()
            ? Response::allow()
            : Response::deny("You've reached the limit of {$user->recipeLimit()} recipes.");
    }

    public function update(User $user, Recipe $recipe): Response
    {
        return $recipe->isOwnedBy($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, Recipe $recipe): Response
    {
        return $this->update($user, $recipe);
    }
}
