<?php

namespace App\Support;

use App\Enums\Collection;
use App\Enums\Cuisine;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\Method;
use App\Enums\RecipeSort;
use App\Enums\TimeFilter;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one place recipe searching, filtering and sorting happens. Every value comes from the
 * URL, so each is checked against an enum or a range; anything unknown is ignored.
 */
final class RecipeFilters
{
    public const MAX_TERMS = 6;

    public string $search;

    public ?MealType $meal;

    public ?Cuisine $cuisine;

    public ?Method $method;

    public ?Difficulty $difficulty;

    public ?TimeFilter $time;

    public ?int $rating;

    /** @var list<Collection> */
    public array $collections;

    /** @var list<DietaryTag> */
    public array $diets;

    public RecipeSort $sort;

    /**
     * @param  array<mixed>  $collections  Collection slugs, straight from the URL.
     * @param  array<mixed>  $diets  Dietary tag slugs, straight from the URL.
     */
    public function __construct(
        ?string $search = null,
        ?string $meal = null,
        ?string $cuisine = null,
        ?string $method = null,
        ?string $difficulty = null,
        ?string $time = null,
        int|string|null $rating = null,
        array $collections = [],
        array $diets = [],
        ?string $sort = null,
        RecipeSort $defaultSort = RecipeSort::Newest,
    ) {
        $this->search = trim(mb_substr((string) $search, 0, 100));
        $this->meal = MealType::fromSlug($meal);
        $this->cuisine = Cuisine::fromSlug($cuisine);
        $this->method = Method::fromSlug($method);
        $this->difficulty = Difficulty::fromSlug($difficulty);
        $this->time = TimeFilter::fromSlug($time);
        $this->rating = in_array((int) $rating, [2, 3, 4], true) ? (int) $rating : null;
        $this->collections = array_values(array_unique(array_filter(array_map(fn ($slug) => Collection::fromSlug(is_string($slug) ? $slug : null), $collections)), SORT_REGULAR));
        $this->diets = array_values(array_unique(array_filter(array_map(fn ($slug) => DietaryTag::fromSlug(is_string($slug) ? $slug : null), $diets)), SORT_REGULAR));
        $this->sort = RecipeSort::fromSlug($sort) ?? $defaultSort;
    }

    /**
     * How many filters are on, not counting search and sort.
     */
    public function count(): int
    {
        return count(array_filter([$this->meal, $this->cuisine, $this->method, $this->difficulty, $this->time, $this->rating]))
            + count($this->collections) + count($this->diets);
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);

        $query
            ->when($this->meal, fn (Builder $q, MealType $meal) => $q->where('meal_type', $meal))
            ->when($this->cuisine, fn (Builder $q, Cuisine $cuisine) => $q->where('cuisine', $cuisine))
            ->when($this->method, fn (Builder $q, Method $method) => $q->where('method', $method))
            ->when($this->difficulty, fn (Builder $q, Difficulty $difficulty) => $q->where('difficulty', $difficulty))
            ->when($this->rating, fn (Builder $q, int $rating) => $q->where('rating_count', '>', 0)->where('rating_avg', '>=', $rating))
            ->when($this->time, function (Builder $q, TimeFilter $time) {
                $q->where('total_minutes', '>', 0);

                match ($time) {
                    TimeFilter::Fifteen => $q->where('total_minutes', '<=', 15),
                    TimeFilter::Thirty => $q->where('total_minutes', '<=', 30),
                    TimeFilter::Sixty => $q->where('total_minutes', '<=', 60),
                    TimeFilter::Longer => $q->where('total_minutes', '>', 60),
                };
            });

        // Every chosen collection and dietary tag must be on the recipe.
        foreach ($this->collections as $collection) {
            $query->whereJsonContains('collections', $collection->value);
        }

        foreach ($this->diets as $diet) {
            $query->whereJsonContains('dietary_tags', $diet->value);
        }

        return $this->applySort($query);
    }

    /**
     * Each word must appear in the title, summary, an ingredient or the cook's name.
     *
     * @param  Builder<Recipe>  $query
     */
    private function applySearch(Builder $query): void
    {
        $terms = collect(preg_split('/\s+/u', $this->search, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $term) => mb_strlen($term) >= 2)
            ->unique()
            ->take(self::MAX_TERMS);

        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '\\%_').'%';

            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhereHas('ingredients', fn (Builder $i) => $i->where('name', 'like', $like))
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like)));
        }
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private function applySort(Builder $query): Builder
    {
        $sorted = match ($this->sort) {
            RecipeSort::Newest => $query->orderByDesc('published_at')->orderByDesc('created_at'),
            RecipeSort::Updated => $query->orderByDesc('updated_at'),
            RecipeSort::TopRated => $query->orderByDesc('rating_avg')->orderByDesc('rating_count')->orderByDesc('published_at'),
            RecipeSort::MostSaved => $query->orderByDesc('saves_count')->orderByDesc('published_at'),
            // Recipes without a time go last.
            RecipeSort::Quickest => $query->orderByRaw('total_minutes = 0')->orderBy('total_minutes')->orderBy('title'),
            RecipeSort::Title => $query->orderBy('title'),
            RecipeSort::Saved => $query->orderByDesc('saved_at'),
        };

        // A final tie-breaker keeps "Show more" pages stable.
        return $sorted->orderByDesc('recipes.id');
    }
}
