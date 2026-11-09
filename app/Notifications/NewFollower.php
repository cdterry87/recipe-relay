<?php

namespace App\Notifications;

use App\Enums\EmailPreference;
use App\Models\User;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "X started following you." In-app always; by email too when the member opts in.
 * Data carries UUIDs and names only, never database ids.
 */
class NewFollower extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $follower)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && $notifiable->wantsEmail(EmailPreference::NewFollower)
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->follower->name} started following you")
            ->view(['mail.new-follower', 'mail.new-follower-text'], [...$this->toArray($notifiable), 'name' => $notifiable->name, 'unsubscribeUrl' => Unsubscribe::url($notifiable, EmailPreference::NewFollower), 'settingsUrl' => route('settings.notifications')])
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()
                ->addTextHeader('List-Unsubscribe', '<'.Unsubscribe::url($notifiable, EmailPreference::NewFollower).'>')
                ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click'));
    }

    public function databaseType(object $notifiable): string
    {
        return 'new-follower';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor' => ['uuid' => $this->follower->uuid, 'name' => $this->follower->name],
        ];
    }
}
