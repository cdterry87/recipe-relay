<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * In-app notifications, newest first. Opening the page marks them read; the ones that were
 * new stay highlighted until the next visit.
 */
new #[Layout('layouts::app', ['width' => 'reading']), Title('Notifications')] class extends Component
{
    public const PER_PAGE = 30;

    public int $shown = self::PER_PAGE;

    /** @var list<string> */
    public array $fresh = [];

    public function mount(): void
    {
        $unread = $this->user()->unreadNotifications();
        $this->fresh = $unread->clone()->latest()->limit(100)->pluck('id')->all();
        $unread->update(['read_at' => now()]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->user()->notifications()->limit($this->shown)->get();
    }

    #[Computed]
    public function total(): int
    {
        return $this->user()->notifications()->count();
    }

    /**
     * The people named in the notifications, keyed by UUID, for their avatars.
     *
     * @return Collection<string, User>
     */
    #[Computed]
    public function actors(): Collection
    {
        $uuids = $this->notifications->pluck('data.actor.uuid')->filter()->unique()->values();

        return User::query()->whereIn('uuid', $uuids)->get()->keyBy('uuid');
    }

    public function loadMore(): void
    {
        $this->shown = min($this->shown + self::PER_PAGE, 300);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
};
?>

<div class="stack stack--lg">
    <header class="section__header">
        <p class="eyebrow">What's new</p>
        <h1><em>Notifications</em></h1>
    </header>

    @if ($this->total === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">🔔</span>
            <h2>All quiet in the <em>kitchen</em></h2>
            <p class="muted">You'll hear here when someone follows you, rates one of your recipes, or a cook you follow shares something new.</p>
        </section>
    @else
        <ul class="notice-list">
            @foreach ($this->notifications as $notification)
                @php
                    $data = $notification->data;
                    $actor = $this->actors->get($data['actor']['uuid'] ?? '');
                    $name = $data['actor']['name'] ?? 'Someone';
                    $recipeTitle = $data['recipe']['title'] ?? null;
                    $href = match ($notification->type) {
                        'new-follower' => $actor ? route('creators.show', $actor) : null,
                        default => isset($data['recipe']['uuid']) ? route('recipes.show', $data['recipe']['uuid']) : null,
                    };
                @endphp
                <li @class(['notice-list__item', 'is-new' => in_array($notification->id, $fresh, true)]) wire:key="note-{{ $notification->id }}">
                    @if ($actor)
                        <x-ui.avatar :user="$actor" size="sm" />
                    @else
                        <x-ui.avatar :name="$name" size="sm" />
                    @endif
                    <div class="notice-list__body">
                        <p>
                            @switch($notification->type)
                                @case('new-follower')
                                    <strong>{{ $name }}</strong> started following you.
                                    @break
                                @case('recipe-rated')
                                    <strong>{{ $name }}</strong> gave <strong>{{ $recipeTitle }}</strong> {{ (int) ($data['stars'] ?? 0) }}★.
                                    @break
                                @case('new-recipe')
                                    <strong>{{ $name }}</strong> shared a new recipe: <strong>{{ $recipeTitle }}</strong>.
                                    @break
                                @default
                                    Something new happened.
                            @endswitch
                        </p>
                        <p class="notice-list__meta">
                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
                            @if (in_array($notification->id, $fresh, true))<span class="notice-list__new">New</span>@endif
                        </p>
                    </div>
                    @if ($href)
                        <a class="notice-list__link" href="{{ $href }}" wire:navigate>
                            View<span class="visually-hidden"> {{ $notification->type === 'new-follower' ? $name.'\'s profile' : $recipeTitle }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($this->total > $this->notifications->count())
            <div class="load-more">
                <x-ui.button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled">Show more</x-ui.button>
            </div>
        @endif
    @endif
</div>
