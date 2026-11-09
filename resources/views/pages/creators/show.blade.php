<?php

use App\Livewire\RecipeListing;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

/**
 * A creator's profile: who they are and their public recipes, filterable like Discover.
 * Every member has a profile; there are no private ones (only private recipes).
 */
new #[Layout('layouts::app')] class extends RecipeListing
{
    #[Locked]
    public string $creatorUuid = '';

    public function mount(User $creator): void
    {
        $this->creatorUuid = $creator->uuid;
    }

    #[Computed]
    public function creator(): User
    {
        return User::query()
            ->withCount(['followers', 'recipes as public_recipes_count' => fn (Builder $q) => $q->listed()])
            ->where('uuid', $this->creatorUuid)
            ->firstOrFail();
    }

    #[On('followed')]
    public function refreshCounts(): void
    {
        unset($this->creator);
    }

    public function rendering($view): void
    {
        $view->title($this->creator->name);
    }

    protected function baseQuery(): Builder
    {
        return Recipe::query()->listed()->whereBelongsTo($this->creator, 'user');
    }
};
?>

@php($creator = $this->creator)

<div class="stack stack--lg">
    <header class="profile-hero">
        <x-ui.avatar :user="$creator" size="lg" />
        <div class="profile-hero__body">
            <p class="eyebrow">Creator</p>
            <h1 class="profile-hero__name">{{ $creator->name }}</h1>
            @if ($creator->bio)<p class="profile-hero__bio">{{ $creator->bio }}</p>@endif
            <p class="profile-hero__stats">
                <span><strong>{{ number_format($creator->public_recipes_count) }}</strong> {{ str('recipe')->plural($creator->public_recipes_count) }}</span>
                <span><strong>{{ number_format($creator->followers_count) }}</strong> {{ str('follower')->plural($creator->followers_count) }}</span>
            </p>
        </div>
        <div class="profile-hero__actions">
            @if (auth()->user()?->is($creator))
                <x-ui.button variant="secondary" size="sm" icon="pencil" :href="route('settings.profile')" wire:navigate>Edit profile</x-ui.button>
            @else
                <livewire:follow-button :creator="$creator" size="md" />
            @endif
        </div>
    </header>

    @if ($creator->public_recipes_count === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">🍳</span>
            <h2>Nothing on the menu <em>yet</em></h2>
            <p class="muted">{{ $creator->name }} hasn't shared a public recipe. Check back soon.</p>
        </section>
    @else
        @include('recipes.partials.filters', ['placeholder' => 'Search '.$creator->name.'\'s recipes'])
        @include('recipes.partials.results')
    @endif
</div>
