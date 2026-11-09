<?php

namespace App\Notifications;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * "Y rated your recipe." Sent the first time a member rates a recipe, not on later changes.
 */
class RecipeRated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $rater, public Recipe $recipe, public int $stars)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'recipe-rated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor' => ['uuid' => $this->rater->uuid, 'name' => $this->rater->name],
            'recipe' => ['uuid' => $this->recipe->uuid, 'title' => $this->recipe->title],
            'stars' => $this->stars,
        ];
    }
}
