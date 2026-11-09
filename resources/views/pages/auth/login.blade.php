<?php

use App\Support\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['width' => 'narrow']), Title('Log in')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Five tries per account from one address, and twenty per address across all accounts,
        // which stops one machine from trying a common password against many emails.
        $key = $this->throttleKey();
        $ipKey = 'login-ip|'.request()->ip();

        foreach ([$key => 5, $ipKey => 20] as $limiterKey => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
                $this->reset('password');

                throw ValidationException::withMessages([
                    'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($limiterKey)]),
                ]);
            }
        }

        $credentials = ['email' => $this->email, 'password' => $this->password];

        // The password is never sent back to the browser in the component's state.
        $this->reset('password');

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(route('home'));
    }

    private function throttleKey(): string
    {
        return 'login|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }
};
?>

<x-ui.card class="auth">
    <header class="auth__header">
        <h1>Welcome back!</h1>
        <p class="auth__lead">Log in to get cooking.</p>
    </header>

    <x-auth.google-button />

    <form class="stack" wire:submit="login" novalidate>
        <x-ui.field name="email" label="Email" type="email" autocomplete="email" required autofocus wire:model="email" />
        <x-ui.field name="password" label="Password" type="password" autocomplete="current-password" required wire:model="password" />

        <div class="auth__row">
            <x-ui.checkbox name="remember" label="Remember me" wire:model="remember" />
            <a href="{{ route('password.request') }}" wire:navigate>Forgot password?</a>
        </div>

        <x-ui.button type="submit" block wire:loading.attr="disabled">Log in</x-ui.button>
    </form>

    @if (Registration::open())
        <p class="auth__footer">
            New to {{ config('app.name') }}? <a href="{{ route('register') }}" wire:navigate>Create an account</a>
        </p>
    @endif
</x-ui.card>
