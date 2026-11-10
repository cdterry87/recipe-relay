<?php

namespace App\Support;

use App\Enums\EmailPreference;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Signed one-click unsubscribe links. They never expire, identify the member by UUID, and
 * only ever turn one kind of email off.
 */
final class Unsubscribe
{
    public static function url(User $user, EmailPreference $preference): string
    {
        return URL::signedRoute('unsubscribe', ['user' => $user->uuid, 'type' => $preference->value]);
    }
}
