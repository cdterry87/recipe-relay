<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['width' => 'narrow']), Title('Forgot password')] class extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        $key = 'forgot-password|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);

        // The result is ignored on purpose: the page never reveals whether an account exists.
        // The shared demo account and sample creators never get a link.
        $protected = User::query()
            ->where('email', $this->email)
            ->where(fn ($query) => $query->where('is_demo', true)->orWhere('is_sample', true))
            ->exists();

        if (! $protected) {
            Password::sendResetLink(['email' => $this->email]);
        }

        $this->sent = true;
    }
};
?>

<x-ui.card class="auth">
    <header class="auth__header">
        <h1>Forgot your <em>password?</em></h1>
        <p class="auth__lead">No problem. Enter your email and we'll send you a link to choose a new one.</p>
    </header>

    @if ($sent)
        <x-ui.notice role="status">
            If an account exists for that email, a reset link is on its way. The link expires in {{ config('auth.passwords.users.expire') }} minutes.
        </x-ui.notice>
    @endif

    <form class="stack" wire:submit="sendLink" novalidate>
        <x-ui.field name="email" label="Email" type="email" autocomplete="email" required autofocus wire:model="email" />
        <x-ui.button type="submit" block wire:loading.attr="disabled">Send reset link</x-ui.button>
    </form>

    <p class="auth__footer">
        Remembered it? <a href="{{ route('login') }}" wire:navigate>Back to log in</a>
    </p>
</x-ui.card>
