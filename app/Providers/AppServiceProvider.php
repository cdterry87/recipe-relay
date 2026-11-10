<?php

namespace App\Providers;

use App\Http\Middleware\EnsureNotDemo;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One password rule for registration, resets, settings and `user:create`.
        Password::defaults(fn () => Password::min(8)->max(255));

        if (filled($proxies = config('relay.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map(trim(...), explode(',', $proxies)));
        }

        // Signs out every other session when the password changes. Livewire re-runs it on
        // component updates, so a stolen session can't keep working either.
        Livewire::addPersistentMiddleware([AuthenticateSession::class, EnsureNotDemo::class]);

        // Verification links name the account by its UUID, never its id.
        VerifyEmail::createUrlUsing(fn (User $user) => URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['user' => $user->uuid, 'hash' => sha1($user->getEmailForVerification())],
        ));

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
