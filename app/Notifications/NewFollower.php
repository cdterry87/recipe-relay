<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * "X started following you." In-app only for now; email arrives with preferences in M5.
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
        return ['database'];
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
