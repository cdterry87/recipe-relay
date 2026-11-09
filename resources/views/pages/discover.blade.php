<?php

use App\Livewire\RecipeListing;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Discover: every public recipe, searchable and filterable.
 */
new #[Layout('layouts::app'), Title('Discover recipes')] class extends RecipeListing
{
    protected function baseQuery(): Builder
    {
        return Recipe::query()->listed();
    }
};
?>

<div class="stack stack--lg">
    <header class="section__header">
        <p class="eyebrow">Find your next favorite</p>
        <h1>Discover <em>recipes</em></h1>
    </header>

    @include('recipes.partials.filters')
    @include('recipes.partials.results')
</div>
