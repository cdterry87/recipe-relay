<?php

use App\Models\User;
use App\Rules\Turnstile;
use App\Support\DemoWorld;
use App\Support\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['width' => 'narrow']), Title('Log in')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /** Turnstile token for the demo button, so only people (not bots) get the one-click login. */
    public string $turnstile = '';

    #[Computed]
    public function demo(): bool
    {
        return config('relay.demo_mode') && DemoWorld::demoConfigured();
    }

    /**
     * Signs in as the shared demo account after a Turnstile check. The credentials never
     * reach the browser.
     */
    public function loginAsDemo(): void
    {
        abort_unless($this->demo, 404);

        $key = 'demo-login|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'turnstile' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 600);

        try {
            $this->validate(
                ['turnstile' => ['required', new Turnstile(request()->ip())]],
                ['turnstile.required' => 'Please complete the security check and try again.'],
            );
        } finally {
            // Turnstile tokens are single-use, so every attempt needs a fresh one.
            $this->reset('turnstile');
            $this->dispatch('turnstile-reset');
        }

        $demo = User::query()->where('is_demo', true)->where('email', config('relay.demo.email'))->first();

        if ($demo === null) {
            throw ValidationException::withMessages(['turnstile' => 'The demo is being set up. Please try again in a minute.']);
        }

        Auth::login($demo);
        session()->regenerate();

        $this->redirectRoute('home');
    }

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
        <h1>Welcome <em>back</em></h1>
        <p class="auth__lead">Log in to get cooking.</p>
    </header>

    @if ($this->demo)
        <section class="demo-panel" aria-labelledby="demo-title">
            <h2 class="demo-panel__title" id="demo-title">Try the <em>demo</em></h2>
            <p class="demo-panel__note">Look around with sample recipes, cooks and ratings. The demo data resets every night.</p>
            {{-- Stays hidden unless Cloudflare wants a click to prove the visitor is a person. --}}
            <div class="demo-panel__check" wire:ignore x-data="turnstile(@js(config('relay.turnstile.site_key')), { appearance: 'interaction-only', action: 'demo-login' })"></div>
            @error('turnstile')<p class="field__error" role="alert">{{ $message }}</p>@enderror
            <x-ui.button variant="secondary" block icon="sparkles" wire:click="loginAsDemo" wire:loading.attr="disabled" wire:target="loginAsDemo">Log in as the demo user</x-ui.button>
        </section>
        <p class="auth__divider">or use your account</p>
    @endif

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
