<?php

use App\Enums\RecipeSort;
use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Livewire\RecipeListing;
use App\Models\Recipe;
use App\Models\Save;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * My cookbook: recipes I've saved. A saved recipe its owner has since made private drops
 * out of view, and comes back if they share it again.
 */
new #[Layout('layouts::app'), Title('My cookbook')] class extends RecipeListing
{
    protected function baseQuery(): Builder
    {
        $saves = Save::query()->whereBelongsTo(auth()->user());

        return Recipe::query()
            ->select('recipes.*')
            ->addSelect(['saved_at' => (clone $saves)->select('created_at')->whereColumn('recipe_id', 'recipes.id')->limit(1)])
            ->whereIn('recipes.id', (clone $saves)->select('recipe_id'))
            ->whereIn('visibility', [Visibility::Public, Visibility::Unlisted])
            ->where('status', RecipeStatus::Complete);
    }

    protected function defaultSort(): RecipeSort
    {
        return RecipeSort::Saved;
    }

    protected function sortOptions(): array
    {
        return [RecipeSort::Saved, RecipeSort::TopRated, RecipeSort::Quickest, RecipeSort::Title, RecipeSort::Newest];
    }

    #[Computed]
    public function saved(): int
    {
        return $this->baseQuery()->count();
    }
};
?>

<div class="stack stack--lg">
    <header class="section__header">
        <p class="eyebrow">Your kitchen</p>
        <h1>My <em>cookbook</em></h1>
        <p class="muted">Recipes you've saved. Once a recipe is in your cookbook, you can rate it.</p>
    </header>

    @if ($this->saved === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">📖</span>
            <h2>No saved recipes <em>yet</em></h2>
            <p class="muted">Tap Save on any recipe you'd like to keep. It will wait for you here.</p>
            <x-ui.button icon="search" :href="route('discover')" wire:navigate>Discover recipes</x-ui.button>
        </section>
    @else
        @include('recipes.partials.filters', ['placeholder' => 'Search my cookbook'])
        @include('recipes.partials.results')
    @endif
</div>
