@php
    use App\Enums\Collection;
    use App\Enums\Cuisine;
    use App\Enums\DietaryTag;
    use App\Enums\Difficulty;
    use App\Enums\MealType;
    use App\Enums\Method;
    use App\Enums\TimeFilter;

    $filters = $this->filters;
    $showCollections ??= true;
    $placeholder ??= 'Search recipes, ingredients or cooks';
@endphp

{{-- The shared filter bar. Used by pages that extend App\Livewire\RecipeListing. --}}
<section class="filter-bar" aria-label="Search and filters">
    <div class="filter-bar__top">
        <div class="search-field">
            <label class="visually-hidden" for="recipe-search">Search</label>
            <x-ui.icon name="search" class="search-field__icon" />
            <input class="search-field__input" id="recipe-search" type="search" placeholder="{{ $placeholder }}" maxlength="100" autocomplete="off" wire:model.live.debounce.400ms="search">
        </div>
        <div class="filter-bar__sort">
            <label class="field__label" for="recipe-sort">Sort</label>
            <select class="field__control" id="recipe-sort" wire:model.live="sort">
                @foreach ($this->sortOptions() as $option)
                    <option value="{{ $option === $this->defaultSort() ? '' : $option->slug() }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <details class="filter-bar__more" @if ($filters->count() > 0) open @endif>
        <summary class="filter-bar__toggle">
            <x-ui.icon name="adjustments" size="sm" />
            Filters
            @if ($filters->count() > 0)<span class="filter-bar__count">{{ $filters->count() }}</span>@endif
        </summary>

        <div class="filter-bar__panel">
            <div class="filter-bar__selects">
                @foreach ([
                    ['meal', 'Meal', 'Any meal', MealType::slugOptions()],
                    ['cuisine', 'Cuisine', 'Any cuisine', Cuisine::slugOptions()],
                    ['method', 'Method', 'Any method', Method::slugOptions()],
                    ['difficulty', 'Difficulty', 'Any level', Difficulty::slugOptions()],
                    ['time', 'Total time', 'Any time', TimeFilter::slugOptions()],
                    ['rating', 'Rating', 'Any rating', ['4' => '4 stars & up', '3' => '3 stars & up', '2' => '2 stars & up']],
                ] as [$name, $label, $any, $options])
                    <div class="field">
                        <label class="field__label" for="filter-{{ $name }}">{{ $label }}</label>
                        <select class="field__control" id="filter-{{ $name }}" wire:model.live="{{ $name }}">
                            <option value="">{{ $any }}</option>
                            @foreach ($options as $value => $text)
                                <option value="{{ $value }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            @if ($showCollections)
                <div class="filter-bar__chips" role="group" aria-labelledby="filter-collections">
                    <p class="field__label" id="filter-collections">Collections</p>
                    <div class="cluster">
                        @foreach (Collection::cases() as $collection)
                            <button type="button" class="toggle-chip" aria-pressed="{{ in_array($collection, $filters->collections, true) ? 'true' : 'false' }}" wire:click="toggleCollection('{{ $collection->slug() }}')">{{ $collection->label() }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="filter-bar__chips" role="group" aria-labelledby="filter-diets">
                <p class="field__label" id="filter-diets">Dietary</p>
                <div class="cluster">
                    @foreach (DietaryTag::cases() as $diet)
                        <button type="button" class="toggle-chip" aria-pressed="{{ in_array($diet, $filters->diets, true) ? 'true' : 'false' }}" wire:click="toggleDiet('{{ $diet->slug() }}')">{{ $diet->label() }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </details>

    <div class="filter-bar__status">
        <p role="status" aria-live="polite">
            <strong>{{ trans_choice(':count recipe|:count recipes', $this->total) }}</strong>
            <span class="muted" wire:loading.delay>· Updating…</span>
        </p>
        <div class="cluster">
            @if ($filters->count() > 0 || $filters->search !== '')
                <x-ui.button variant="ghost" size="sm" icon="x" wire:click="clearFilters">Clear all</x-ui.button>
            @endif
            @if ($showSurprise ?? true)
                <x-ui.button variant="secondary" size="sm" icon="sparkles" wire:click="surprise" :disabled="$this->total === 0">Surprise me</x-ui.button>
            @endif
        </div>
    </div>
</section>
