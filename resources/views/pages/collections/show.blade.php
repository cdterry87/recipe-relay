<?php

use App\Enums\Collection;
use App\Livewire\RecipeListing;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;

/**
 * One collection, such as Quick & Easy: its public recipes, filterable like Discover.
 */
new #[Layout('layouts::app')] class extends RecipeListing
{
    #[Locked]
    public string $slug = '';

    public function mount(string $collection): void
    {
        abort_if(Collection::fromSlug($collection) === null, 404);

        $this->slug = $collection;
    }

    public function collection(): Collection
    {
        return Collection::fromSlug($this->slug);
    }

    public function rendering($view): void
    {
        $view->title($this->collection()->label());
    }

    protected function baseQuery(): Builder
    {
        return Recipe::query()->listed()->whereJsonContains('collections', $this->collection()->value);
    }
};
?>

<div class="stack stack--lg">
    <header class="collection-hero">
        <span class="collection-hero__emoji" aria-hidden="true">{{ $this->collection()->emoji() }}</span>
        <div class="section__header">
            <p class="eyebrow">Collection</p>
            <h1>{{ $this->collection()->label() }}</h1>
            <p class="muted">{{ $this->collection()->description() }}</p>
        </div>
    </header>

    <nav class="cluster collection-nav" aria-label="Other collections">
        @foreach (Collection::cases() as $other)
            <a class="toggle-chip" href="{{ route('collections.show', $other->slug()) }}" wire:navigate @if ($other === $this->collection()) aria-current="page" @endif>{{ $other->label() }}</a>
        @endforeach
    </nav>

    @include('recipes.partials.filters', ['showCollections' => false, 'placeholder' => 'Search '.$this->collection()->label().' recipes'])
    @include('recipes.partials.results')
</div>
