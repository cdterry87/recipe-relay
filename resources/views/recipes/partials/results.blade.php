{{-- Recipe cards for a RecipeListing page, with "Show more" and an empty state. --}}
@if ($this->total === 0)
    <section class="empty-state">
        <span class="empty-state__art" aria-hidden="true">🔍</span>
        <h2>No recipes <em>match</em></h2>
        <p class="muted">Try a different word, or loosen a filter or two.</p>
        @if ($this->filters->count() > 0 || $this->filters->search !== '')
            <x-ui.button variant="secondary" wire:click="clearFilters">Clear all filters</x-ui.button>
        @endif
    </section>
@else
    {{-- Card titles are h3s, so the list gets its own (hidden) h2 under the page's h1. --}}
    <h2 class="visually-hidden">{{ $resultsHeading ?? 'Recipes' }}</h2>
    <div class="card-grid">
        @foreach ($this->results as $recipe)
            <x-recipe.tile :recipe="$recipe" wire:key="recipe-{{ $recipe->uuid }}">
                @if ($showStatus ?? false)
                    <x-ui.chip :tone="$recipe->isComplete() ? 'secondary' : 'subtle'">{{ $recipe->status->label() }}</x-ui.chip>
                    <x-ui.chip>{{ $recipe->visibility->label() }}</x-ui.chip>
                @endif
            </x-recipe.tile>
        @endforeach
    </div>
    @if ($this->total > $this->results->count())
        <div class="load-more">
            <x-ui.button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled">Show more</x-ui.button>
        </div>
    @endif
@endif
