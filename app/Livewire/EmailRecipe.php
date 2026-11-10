<?php

namespace App\Livewire;

use App\Mail\RecipeShared;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Email a friend" in the share sheet. Members only, five an hour each, never the demo
 * account, and only for recipes that can be shared.
 *
 * @property-read Recipe $recipe
 */
class EmailRecipe extends Component
{
    public const PER_HOUR = 5;

    #[Locked]
    public string $recipeUuid = '';

    public string $email = '';

    public string $note = '';

    public bool $sent = false;

    public function mount(Recipe $recipe): void
    {
        $this->recipeUuid = $recipe->uuid;
    }

    #[Computed]
    public function recipe(): Recipe
    {
        return Recipe::query()->with('user')->where('uuid', $this->recipeUuid)->firstOrFail();
    }

    public function send(): void
    {
        $user = $this->user();

        if ($reason = $this->blockedReason($user)) {
            $this->addError('email', $reason);

            return;
        }

        $this->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'note' => ['nullable', 'string', 'max:300'],
        ], [
            'email.required' => "Enter your friend's email address.",
            'email.email' => 'Enter a valid email address, such as friend@example.com.',
        ]);

        $key = 'email-recipe|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('email', "You've shared a lot this hour. Try again in {$minutes} ".str('minute')->plural($minutes).'.');

            return;
        }

        RateLimiter::hit($key, 3600);

        Mail::to(trim($this->email))->queue(new RecipeShared($this->recipe, $user, filled(trim($this->note)) ? trim($this->note) : null));

        $this->reset('email', 'note');
        $this->sent = true;
        $this->dispatch('toast', type: 'success', message: 'Sent! Your friend will get it in a moment.');
    }

    public function again(): void
    {
        $this->sent = false;
    }

    public function blockedReason(?User $user): ?string
    {
        return match (true) {
            $user === null => 'Log in to email a recipe.',
            $user->is_demo => 'Emailing recipes is turned off for the demo account. Try the other share options.',
            ! $user->hasVerifiedEmail() => 'Confirm your email address before emailing recipes.',
            ! $this->recipe->visibility->isShared() || ! $this->recipe->isComplete() => 'Only public and unlisted recipes can be shared.',
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.email-recipe', ['blocked' => $this->blockedReason($this->user())]);
    }

    private function user(): ?User
    {
        /** @var User|null */
        return auth()->user();
    }
}
