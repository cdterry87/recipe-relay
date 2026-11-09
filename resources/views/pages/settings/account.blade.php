<?php

use App\Support\Avatars;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app', ['width' => 'reading']), Title('Account settings')] class extends Component
{
    public string $email = '';

    public string $email_password = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $delete_confirmation = '';

    public function mount(): void
    {
        $this->email = auth()->user()->email;
    }

    public function updateEmail(): void
    {
        $user = auth()->user();

        if (mb_strtolower(trim($this->email)) === mb_strtolower($user->email)) {
            return;
        }

        try {
            $validated = $this->throttlePasswordChecks('email_password', fn () => $this->validate([
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id), Rule::notIn(array_filter([config('relay.demo.email')]))],
                'email_password' => $user->hasPassword() ? ['required', 'string', 'current_password'] : ['nullable'],
            ], [
                'email.not_in' => 'That email is reserved.',
                'email_password.required' => 'Enter your current password to change your email.',
                'email_password.current_password' => 'The password is incorrect.',
            ], ['email_password' => 'password']));
        } finally {
            $this->reset('email_password');
        }

        // A new address has to be confirmed again.
        $user->forceFill([
            'email' => trim($validated['email']),
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        $this->dispatch('toast', type: 'success', message: 'Your email has been changed. Check your inbox to confirm it.');
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        try {
            $validated = $this->throttlePasswordChecks('current_password', fn () => $this->validate([
                'current_password' => $user->hasPassword() ? ['required', 'string', 'current_password'] : ['nullable'],
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ], [
                'current_password.current_password' => 'The password is incorrect.',
            ]));
        } finally {
            // Never send passwords back to the browser.
            $this->reset('current_password', 'password', 'password_confirmation');
        }

        // The new password hash signs out every other session (AuthenticateSession), and the
        // new remember token their "remember me" cookies.
        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Keep this session: Livewire updates skip the step where the middleware would
        // record the new hash itself.
        session()->regenerate();
        session()->put('password_hash_'.Auth::getDefaultDriver(), $user->getAuthPassword());

        $this->dispatch('toast', type: 'success', message: 'Your password has been saved.');
    }

    public function deleteAccount(): void
    {
        $user = auth()->user();

        try {
            $this->throttlePasswordChecks('delete_confirmation', fn () => $this->validate([
                'delete_confirmation' => $user->hasPassword()
                    ? ['required', 'string', 'current_password']
                    : ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                        if (mb_strtolower(trim((string) $value)) !== mb_strtolower($user->email)) {
                            $fail('Type your email address exactly to confirm.');
                        }
                    }],
            ], [
                'delete_confirmation.required' => $user->hasPassword() ? 'Enter your password to confirm.' : 'Type your email address to confirm.',
                'delete_confirmation.current_password' => 'The password is incorrect.',
            ]));
        } finally {
            $this->reset('delete_confirmation');
        }

        Auth::logout();
        Avatars::delete($user->avatar_path);
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();
        session()->flash('toast', ['type' => 'success', 'message' => 'Your account has been deleted. Thanks for cooking with us.']);

        $this->redirectRoute('home');
    }

    /**
     * Limits wrong current-password answers to five per five minutes across this page, so
     * someone holding a stolen session can't use it to guess the password.
     */
    private function throttlePasswordChecks(string $field, \Closure $check): mixed
    {
        $key = 'password-check|'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                $field => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        try {
            return $check();
        } catch (ValidationException $exception) {
            if (array_key_exists($field, $exception->errors())) {
                RateLimiter::hit($key, 300);
            }

            throw $exception;
        }
    }
};
?>

<div class="settings">
    @php($user = auth()->user())

    <header class="stack stack--sm">
        <p class="eyebrow">Settings</p>
        <h1>Your <em>account</em></h1>
        <x-settings.nav />
    </header>

    <x-ui.card title="Email">
        <form class="stack" wire:submit="updateEmail" novalidate>
            <x-ui.field name="email" label="Email" type="email" autocomplete="email" required wire:model.live.blur="email"
                :hint="$user->hasVerifiedEmail() ? 'Confirmed.' : 'Not confirmed yet.'" />
            @if ($user->hasPassword() && mb_strtolower(trim($email)) !== mb_strtolower($user->email))
                <x-ui.field name="email_password" label="Current password" type="password" autocomplete="current-password" hint="Required to change your email." required wire:model="email_password" />
            @endif
            <div class="settings__actions">
                <x-ui.button type="submit" wire:loading.attr="disabled">Change email</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :title="$user->hasPassword() ? 'Password' : 'Set a password'">
        <form class="stack" wire:submit="updatePassword" novalidate>
            @if ($user->hasPassword())
                <x-ui.field name="current_password" label="Current password" type="password" autocomplete="current-password" required wire:model="current_password" />
            @else
                <p class="muted">You sign in with Google. Add a password to also log in with your email.</p>
            @endif
            <div class="form-row">
                <x-ui.field name="password" label="New password" type="password" autocomplete="new-password" hint="At least 8 characters." required wire:model="password" />
                <x-ui.field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required wire:model="password_confirmation" />
            </div>
            <div class="settings__actions">
                <x-ui.button type="submit" wire:loading.attr="disabled">Save password</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Delete account" class="danger-zone">
        <form class="stack" wire:submit="deleteAccount" novalidate>
            <p>This permanently deletes your account, your recipes and your photos. It can't be undone.</p>
            @if ($user->hasPassword())
                <x-ui.field name="delete_confirmation" label="Enter your password to confirm" type="password" autocomplete="current-password" required wire:model="delete_confirmation" />
            @else
                <x-ui.field name="delete_confirmation" label="Type your email address to confirm" type="email" autocomplete="off" required wire:model="delete_confirmation" />
            @endif
            <div class="settings__actions">
                <x-ui.button type="submit" variant="danger" icon="trash" wire:loading.attr="disabled">Delete my account</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
