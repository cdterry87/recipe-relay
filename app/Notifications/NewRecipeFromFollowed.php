<?php

namespace App\Notifications;

use App\Models\Recipe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * "Z posted a new recipe." Sent to followers once, the first time a recipe goes public.
 */
class NewRecipeFromFollowed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Recipe $recipe)
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
        return 'new-recipe';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor' => ['uuid' => $this->recipe->user->uuid, 'name' => $this->recipe->user->name],
            'recipe' => ['uuid' => $this->recipe->uuid, 'title' => $this->recipe->title],
        ];
    }
}
