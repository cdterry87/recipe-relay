<?php

use App\Livewire\RecipeListing;
use App\Models\Follow;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * The following feed: the newest public recipes from creators I follow, and who they are.
 */
new #[Layout('layouts::app'), Title('Following')] class extends RecipeListing
{
    protected function baseQuery(): Builder
    {
        return Recipe::query()->listed()->whereIn(
            'user_id',
            Follow::query()->where('follower_id', auth()->id())->select('followee_id'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function creators(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->following()->orderByPivot('created_at', 'desc')->get();
    }

    #[On('followed')]
    public function refreshFeed(): void
    {
        unset($this->creators, $this->results, $this->total);
    }
};
?>

<div class="stack stack--lg">
    <header class="section__header">
        <p class="eyebrow">Your feed</p>
        <h1>From cooks you <em>follow</em></h1>
    </header>

    @if ($this->creators->isEmpty())
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">👩‍🍳</span>
            <h2>You're not following anyone <em>yet</em></h2>
            <p class="muted">Follow a few cooks and their new recipes will show up here, freshest first.</p>
            <x-ui.button icon="users" :href="route('creators')" wire:navigate>Find creators</x-ui.button>
        </section>
    @else
        <section aria-labelledby="following-title" class="following-strip">
            <h2 id="following-title" class="following-strip__title">Following {{ $this->creators->count() }}</h2>
            <ul class="following-strip__list">
                @foreach ($this->creators as $creator)
                    <li class="following-strip__item" wire:key="following-{{ $creator->uuid }}">
                        <a class="following-strip__link" href="{{ route('creators.show', $creator) }}" wire:navigate>
                            <x-ui.avatar :user="$creator" size="md" />
                            <span>{{ $creator->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        @include('recipes.partials.filters', ['placeholder' => 'Search recipes from people you follow'])
        @include('recipes.partials.results')
    @endif
</div>
