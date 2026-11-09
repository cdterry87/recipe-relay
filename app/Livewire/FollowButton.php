<?php

namespace App\Livewire;

use App\Actions\Follows;
use App\Livewire\Concerns\RemembersGuestIntent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Follow and unfollow a creator. Hidden on the member's own recipes and profile.
 *
 * @property-read User $creator
 * @property-read bool $following
 */
class FollowButton extends Component
{
    use RemembersGuestIntent;

    #[Locked]
    public string $creatorUuid = '';

    #[Locked]
    public string $returnUrl = '';

    public string $size = 'sm';

    public function mount(User $creator, string $size = 'sm'): void
    {
        $this->creatorUuid = $creator->uuid;
        $this->returnUrl = url()->current();
        $this->size = $size === 'md' ? 'md' : 'sm';

        if ($this->pendingIntent('follow', $creator->uuid) && Gate::allows('follow', $creator) && ! $this->following) {
            app(Follows::class)->follow($this->user(), $creator);
            unset($this->following);
            $this->dispatch('toast', type: 'success', message: "You're following {$creator->name}.");
        }
    }

    #[Computed]
    public function creator(): User
    {
        return User::query()->where('uuid', $this->creatorUuid)->firstOrFail();
    }

    #[Computed]
    public function following(): bool
    {
        return auth()->check() && $this->user()->isFollowing($this->creator);
    }

    public function toggle(Follows $follows): void
    {
        if (auth()->guest()) {
            $this->rememberAndLogIn('follow', $this->creatorUuid, $this->returnUrl);

            return;
        }

        if ($this->following) {
            $follows->unfollow($this->user(), $this->creator);
            $message = "You've unfollowed {$this->creator->name}.";
        } else {
            $response = Gate::inspect('follow', $this->creator);

            if ($response->denied()) {
                $this->dispatch('toast', type: 'info', message: (string) $response->message());

                return;
            }

            $follows->follow($this->user(), $this->creator);
            $message = "You're following {$this->creator->name}. Their new recipes will show up in your feed.";
        }

        unset($this->following);
        $this->dispatch('followed');
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function render(): View
    {
        return view('livewire.follow-button');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
