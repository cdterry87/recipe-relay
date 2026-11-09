<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the signed link from the "confirm your email" message. The account must be the
 * one signed in, and the hash must match its current email, so a link sent to an old
 * address stops working once the email changes.
 */
class VerifyEmailController
{
    public function __invoke(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless($request->user()->is($user), 403);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('home')->with('toast', ['type' => 'success', 'message' => 'Thanks! Your email is confirmed.']);
    }
}
