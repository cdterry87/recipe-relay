<?php

namespace App\Actions;

use App\Enums\Visibility;
use App\Models\Recipe;
use App\Notifications\NewRecipeFromFollowed;
use Illuminate\Support\Facades\Notification;

/**
 * Tells a creator's followers about a recipe the first time it is public. Making it private
 * and public again, or editing it, never sends a second notice.
 */
final class AnnounceRecipe
{
    public function handle(Recipe $recipe): void
    {
        if ($recipe->visibility !== Visibility::Public || $recipe->announced_at !== null) {
            return;
        }

        // Claim the announcement atomically, so two quick saves can't both send it.
        $claimed = Recipe::query()->whereKey($recipe->getKey())->whereNull('announced_at')->toBase()->update(['announced_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $recipe->refresh()->loadMissing('user');

        $recipe->user->followers()->chunkById(200, function ($followers) use ($recipe) {
            Notification::send($followers, new NewRecipeFromFollowed($recipe));
        }, 'users.id', 'id');
    }
}
