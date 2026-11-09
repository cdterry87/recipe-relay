<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Support\Registration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google sign-in. Signs in an existing account (matched on its Google ID, or on a verified
 * Google email), and creates one only while registration is open.
 */
class GoogleController
{
    public function redirect(): SymfonyRedirect
    {
        abort_unless(Registration::googleEnabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(Registration::googleEnabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->fail("Google sign-in didn't work. Please try again.");
        }

        $email = $google->getEmail();
        $raw = $google instanceof AbstractUser ? $google->getRaw() : [];

        // Only trust an address Google itself has confirmed.
        if (blank($email) || ($raw['email_verified'] ?? false) !== true) {
            return $this->fail('Your Google account needs a confirmed email address to sign in.');
        }

        $user = User::query()->where('google_id', $google->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user?->is_sample || $user?->is_demo) {
            return $this->fail("That account can't sign in with Google.");
        }

        if ($user === null) {
            if (! Registration::open()) {
                return $this->fail('Sign-ups are closed right now, so Google sign-in only works for existing accounts.');
            }

            $user = new User;
            $user->forceFill([
                'name' => $google->getName() ?: $google->getNickname() ?: strstr($email, '@', true),
                'email' => $email,
                'password' => null,
                'google_id' => $google->getId(),
                'email_verified_at' => now(),
            ])->save();

            event(new Registered($user));
        } else {
            $user->google_id ??= $google->getId();

            // Google has confirmed this same address, so the account's email is confirmed too.
            if (! $user->hasVerifiedEmail() && strcasecmp($user->email, $email) === 0) {
                $user->email_verified_at = now();
            }

            $user->save();
        }

        Auth::login($user);
        session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('toast', ['type' => 'error', 'message' => $message]);
    }
}
