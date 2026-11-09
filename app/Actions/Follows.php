<?php

namespace App\Actions;

use App\Models\Follow;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Following and unfollowing creators. Callers authorize first (UserPolicy::follow).
 */
final class Follows
{
    public function follow(User $follower, User $creator): void
    {
        $follow = Follow::query()->firstOrCreate(['follower_id' => $follower->id, 'followee_id' => $creator->id]);

        if (! $follow->wasRecentlyCreated) {
            return;
        }

        // Following, unfollowing and following again leaves one notification, not a pile.
        $this->forgetNotification($follower, $creator);
        $creator->notify(new NewFollower($follower));
    }

    public function unfollow(User $follower, User $creator): void
    {
        Follow::query()->where(['follower_id' => $follower->id, 'followee_id' => $creator->id])->delete();
    }

    private function forgetNotification(User $follower, User $creator): void
    {
        DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $creator)
            ->where('type', 'new-follower')
            ->where('data->actor->uuid', $follower->uuid)
            ->delete();
    }
}
