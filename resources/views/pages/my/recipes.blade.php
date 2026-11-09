<?php

use App\Models\Recipe;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app'), Title('My recipes')] class extends Component
{
    public const PER_PAGE = 24;

    public int $shown = self::PER_PAGE;

    #[Computed]
    public function recipes()
    {
        return Recipe::query()
            ->ownedBy(auth()->user())
            ->with('user')
            ->latest('updated_at')
            ->limit($this->shown)
            ->get();
    }

    #[Computed]
    public function total(): int
    {
        return auth()->user()->recipes()->count();
    }

    public function loadMore(): void
    {
        $this->shown += self::PER_PAGE;
    }
};
?>

<div class="stack stack--lg">
    <div class="section__top">
        <header class="section__header">
            <p class="eyebrow">Your kitchen</p>
            <h1>My <em>recipes</em></h1>
        </header>
        @can('create', App\Models\Recipe::class)
            <x-ui.button icon="plus" :href="route('recipes.create')" wire:navigate>New recipe</x-ui.button>
        @else
            <p class="muted">You've reached the limit of {{ auth()->user()->recipeLimit() }} recipes.</p>
        @endcan
    </div>

    @if ($this->total === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">🥣</span>
            <h2>Your recipe box is <em>empty</em></h2>
            <p class="muted">Start with a family favorite. You can keep it private, or share it when it's ready.</p>
            <x-ui.button icon="plus" :href="route('recipes.create')" wire:navigate>Write your first recipe</x-ui.button>
        </section>
    @else
        <p class="muted">{{ trans_choice(':count recipe|:count recipes', $this->total) }}</p>
        <div class="card-grid">
            @foreach ($this->recipes as $recipe)
                <x-recipe.tile :recipe="$recipe" wire:key="recipe-{{ $recipe->uuid }}">
                    <x-ui.chip :tone="$recipe->isComplete() ? 'secondary' : 'subtle'">{{ $recipe->status->label() }}</x-ui.chip>
                    <x-ui.chip>{{ $recipe->visibility->label() }}</x-ui.chip>
                </x-recipe.tile>
            @endforeach
        </div>
        @if ($this->total > $this->recipes->count())
            <div class="load-more">
                <x-ui.button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled">Show more</x-ui.button>
            </div>
        @endif
    @endif
</div>
