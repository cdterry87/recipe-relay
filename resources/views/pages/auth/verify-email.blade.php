<?php

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['width' => 'narrow']), Title('Confirm your email')] class extends Component
{
    public function mount(): void
    {
        if (auth()->user()->hasVerifiedEmail()) {
            $this->redirectRoute('home');
        }
    }

    public function resend(): void
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectRoute('home');

            return;
        }

        $key = 'verify-email|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->dispatch('toast', type: 'error', message: __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]));

            return;
        }

        RateLimiter::hit($key, 600);

        $user->sendEmailVerificationNotification();

        $this->dispatch('toast', type: 'success', message: 'A new confirmation link is on its way to '.$user->email.'.');
    }
};
?>

<x-ui.card class="auth">
    <header class="auth__header">
        <h1>Check your <em>inbox</em></h1>
        <p class="auth__lead">We sent a confirmation link to <strong>{{ auth()->user()->email }}</strong>. Click it to start sharing your recipes.</p>
    </header>

    <x-ui.button block wire:click="resend" wire:loading.attr="disabled">Send a new link</x-ui.button>

    <p class="auth__footer">
        Wrong address? <a href="{{ route('settings.account') }}" wire:navigate>Change your email</a>
    </p>
</x-ui.card>
