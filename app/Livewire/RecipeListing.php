<?php

namespace App\Livewire;

use App\Enums\RecipeSort;
use App\Models\Recipe;
use App\Support\RecipeFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Base for any page that lists recipes: search, filters, sort and "Show more". The page
 * supplies the base query (public recipes, my recipes, one collection…); state lives in the
 * URL so filtered views can be shared. Pages render the shared partials
 * recipes.partials.filters and recipes.partials.results.
 */
abstract class RecipeListing extends Component
{
    public const PER_PAGE = 24;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $meal = '';

    #[Url(except: '')]
    public string $cuisine = '';

    #[Url(except: '')]
    public string $method = '';

    #[Url(as: 'level', except: '')]
    public string $difficulty = '';

    #[Url(except: '')]
    public string $time = '';

    #[Url(except: '')]
    public string $rating = '';

    /** @var array<mixed> */
    #[Url(as: 'collection', except: [])]
    public array $collections = [];

    /** @var array<mixed> */
    #[Url(as: 'diet', except: [])]
    public array $diets = [];

    #[Url(except: '')]
    public string $sort = '';

    public int $shown = self::PER_PAGE;

    /**
     * @return Builder<Recipe>
     */
    abstract protected function baseQuery(): Builder;

    protected function defaultSort(): RecipeSort
    {
        return RecipeSort::Newest;
    }

    /**
     * Sort orders that make sense on this page.
     *
     * @return list<RecipeSort>
     */
    protected function sortOptions(): array
    {
        return [RecipeSort::Newest, RecipeSort::TopRated, RecipeSort::MostSaved, RecipeSort::Quickest, RecipeSort::Title];
    }

    /**
     * Any change to the filters starts the list again from the top.
     */
    public function updated(string $name): void
    {
        if ($name !== 'shown') {
            $this->shown = self::PER_PAGE;
            unset($this->results, $this->total);
        }
    }

    #[Computed]
    public function filters(): RecipeFilters
    {
        return new RecipeFilters(
            search: $this->search,
            meal: $this->meal,
            cuisine: $this->cuisine,
            method: $this->method,
            difficulty: $this->difficulty,
            time: $this->time,
            rating: $this->rating,
            collections: $this->collections,
            diets: $this->diets,
            sort: in_array(RecipeSort::fromSlug($this->sort), $this->sortOptions(), true) ? $this->sort : null,
            defaultSort: $this->defaultSort(),
        );
    }

    /**
     * @return EloquentCollection<int, Recipe>
     */
    #[Computed]
    public function results(): EloquentCollection
    {
        return $this->filters()->apply($this->baseQuery())->with('user')->limit($this->shown)->get();
    }

    #[Computed]
    public function total(): int
    {
        return $this->filters()->apply($this->baseQuery())->reorder()->count();
    }

    public function loadMore(): void
    {
        $this->shown = min($this->shown + self::PER_PAGE, 500);
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'meal', 'cuisine', 'method', 'difficulty', 'time', 'rating', 'collections', 'diets', 'sort');
        $this->shown = self::PER_PAGE;
    }

    public function toggleCollection(string $slug): void
    {
        $this->collections = $this->toggle($this->collections, $slug);
        $this->shown = self::PER_PAGE;
    }

    public function toggleDiet(string $slug): void
    {
        $this->diets = $this->toggle($this->diets, $slug);
        $this->shown = self::PER_PAGE;
    }

    /**
     * Opens a random recipe from the current results.
     */
    public function surprise(): void
    {
        $recipe = $this->filters()->apply($this->baseQuery())->reorder()->inRandomOrder()->first();

        if ($recipe === null) {
            $this->dispatch('toast', type: 'info', message: 'Nothing to pick from yet. Try fewer filters.');

            return;
        }

        $this->redirect($recipe->shareUrl(), navigate: true);
    }

    /**
     * @param  array<mixed>  $list
     * @return list<mixed>
     */
    private function toggle(array $list, string $value): array
    {
        return in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }
}
