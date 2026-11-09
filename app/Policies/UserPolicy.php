<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Following a creator: anyone else with at least one public recipe. Unfollowing is
     * always allowed, so it needs no check.
     */
    public function follow(User $user, User $creator): Response
    {
        if ($user->is($creator)) {
            return Response::deny("You can't follow yourself.");
        }

        return $creator->isFollowable() ? Response::allow() : Response::deny('This cook has no public recipes to follow yet.');
    }
}
