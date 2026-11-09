<?php

use App\Enums\RecipeSort;
use App\Livewire\RecipeListing;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * My recipes: everything I own, drafts and private ones included.
 */
new #[Layout('layouts::app'), Title('My recipes')] class extends RecipeListing
{
    protected function baseQuery(): Builder
    {
        return Recipe::query()->ownedBy(auth()->user());
    }

    protected function defaultSort(): RecipeSort
    {
        return RecipeSort::Updated;
    }

    protected function sortOptions(): array
    {
        return [RecipeSort::Updated, RecipeSort::Newest, RecipeSort::Title, RecipeSort::Quickest, RecipeSort::TopRated, RecipeSort::MostSaved];
    }

    #[Computed]
    public function owned(): int
    {
        return auth()->user()->recipes()->count();
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

    @if ($this->owned === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">🥣</span>
            <h2>Your recipe box is <em>empty</em></h2>
            <p class="muted">Start with a family favorite. You can keep it private, or share it when it's ready.</p>
            <x-ui.button icon="plus" :href="route('recipes.create')" wire:navigate>Write your first recipe</x-ui.button>
        </section>
    @else
        @include('recipes.partials.filters', ['placeholder' => 'Search my recipes', 'showSurprise' => false])
        @include('recipes.partials.results', ['showStatus' => true])
    @endif
</div>
