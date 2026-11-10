<?php

namespace App\Notifications;

use App\Enums\EmailPreference;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return $notifiable instanceof User && $notifiable->wantsEmail(EmailPreference::NewRating)
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->rater->name} rated {$this->recipe->title}")
            ->view(['mail.recipe-rated', 'mail.recipe-rated-text'], [...$this->toArray($notifiable), 'name' => $notifiable->name, 'unsubscribeUrl' => Unsubscribe::url($notifiable, EmailPreference::NewRating), 'settingsUrl' => route('settings.notifications')])
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()
                ->addTextHeader('List-Unsubscribe', '<'.Unsubscribe::url($notifiable, EmailPreference::NewRating).'>')
                ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click'));
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
