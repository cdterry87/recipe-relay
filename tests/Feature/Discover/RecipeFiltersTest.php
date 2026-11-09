<?php

use App\Enums\Collection;
use App\Enums\DietaryTag;
use App\Enums\RecipeSort;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RecipeFilters;

function titles(RecipeFilters $filters): array
{
    return $filters->apply(Recipe::query()->listed())->pluck('title')->all();
}

it('searches titles, summaries, ingredients and cooks, word by word', function () {
    $julia = User::factory()->create(['name' => 'Julia Child']);
    Recipe::factory()->public()->for($julia)->create(['title' => 'Boeuf Bourguignon', 'summary' => 'A French classic.']);
    Recipe::factory()->public()->create(['title' => 'Tomato Soup', 'summary' => 'Bright and quick.']);
    $stew = Recipe::factory()->public()->create(['title' => 'Winter Stew', 'summary' => null]);
    Ingredient::factory()->create(['recipe_id' => $stew->id, 'name' => 'parsnips', 'position' => 9]);

    expect(titles(new RecipeFilters(search: 'tomato')))->toBe(['Tomato Soup'])
        ->and(titles(new RecipeFilters(search: 'FRENCH')))->toBe(['Boeuf Bourguignon'])
        ->and(titles(new RecipeFilters(search: 'parsnip')))->toBe(['Winter Stew'])
        ->and(titles(new RecipeFilters(search: 'julia')))->toBe(['Boeuf Bourguignon'])
        ->and(titles(new RecipeFilters(search: 'tomato quick')))->toBe(['Tomato Soup'])
        ->and(titles(new RecipeFilters(search: 'tomato french')))->toBe([]);
});

it('treats search wildcards as plain text', function () {
    Recipe::factory()->public()->create(['title' => '100% Rye Bread']);
    Recipe::factory()->public()->create(['title' => 'Plain Toast']);

    expect(titles(new RecipeFilters(search: '100%')))->toBe(['100% Rye Bread'])
        ->and(titles(new RecipeFilters(search: '%%')))->toBe([]);
});

it('filters by meal, cuisine, method and difficulty', function () {
    Recipe::factory()->public()->create(['title' => 'Match', 'meal_type' => 'Breakfast', 'cuisine' => 'French', 'method' => 'Baked', 'difficulty' => 'Hard']);
    Recipe::factory()->public()->create(['title' => 'Other', 'meal_type' => 'Breakfast', 'cuisine' => 'Italian', 'method' => 'Baked', 'difficulty' => 'Hard']);

    expect(titles(new RecipeFilters(meal: 'breakfast')))->toEqualCanonicalizing(['Match', 'Other'])
        ->and(titles(new RecipeFilters(meal: 'breakfast', cuisine: 'french', method: 'baked', difficulty: 'hard')))->toBe(['Match']);
});

it('needs every chosen collection and dietary tag', function () {
    Recipe::factory()->public()->create(['title' => 'Both', 'collections' => [Collection::QuickAndEasy, Collection::OnePot], 'dietary_tags' => [DietaryTag::Vegan, DietaryTag::GlutenFree]]);
    Recipe::factory()->public()->create(['title' => 'One', 'collections' => [Collection::QuickAndEasy], 'dietary_tags' => [DietaryTag::Vegan]]);

    expect(titles(new RecipeFilters(collections: ['quick-and-easy'])))->toEqualCanonicalizing(['Both', 'One'])
        ->and(titles(new RecipeFilters(collections: ['quick-and-easy', 'one-pot'])))->toBe(['Both'])
        ->and(titles(new RecipeFilters(diets: ['vegan', 'gluten-free'])))->toBe(['Both']);
});

it('filters by total time, leaving out recipes without one', function () {
    Recipe::factory()->public()->create(['title' => 'Ten', 'prep_minutes' => 5, 'cook_minutes' => 5]);
    Recipe::factory()->public()->create(['title' => 'Forty', 'prep_minutes' => 10, 'cook_minutes' => 30]);
    Recipe::factory()->public()->create(['title' => 'Ninety', 'prep_minutes' => 30, 'cook_minutes' => 60]);
    Recipe::factory()->public()->create(['title' => 'Unknown', 'prep_minutes' => null, 'cook_minutes' => null]);

    expect(titles(new RecipeFilters(time: '15')))->toBe(['Ten'])
        ->and(titles(new RecipeFilters(time: '60')))->toEqualCanonicalizing(['Ten', 'Forty'])
        ->and(titles(new RecipeFilters(time: 'longer')))->toBe(['Ninety']);
});

it('filters by star rating, leaving out unrated recipes', function () {
    Recipe::factory()->public()->create(['title' => 'Great', 'rating_avg' => 4.5, 'rating_count' => 3]);
    Recipe::factory()->public()->create(['title' => 'Fine', 'rating_avg' => 3.2, 'rating_count' => 5]);
    Recipe::factory()->public()->create(['title' => 'New', 'rating_avg' => 0, 'rating_count' => 0]);

    expect(titles(new RecipeFilters(rating: '4')))->toBe(['Great'])
        ->and(titles(new RecipeFilters(rating: 3)))->toEqualCanonicalizing(['Great', 'Fine']);
});

it('sorts by each allowed order', function () {
    Recipe::factory()->public()->create(['title' => 'B', 'prep_minutes' => 50, 'cook_minutes' => 0, 'saves_count' => 1, 'rating_avg' => 5, 'rating_count' => 1, 'published_at' => now()->subDays(2)]);
    Recipe::factory()->public()->create(['title' => 'A', 'prep_minutes' => 10, 'cook_minutes' => 0, 'saves_count' => 9, 'rating_avg' => 3, 'rating_count' => 1, 'published_at' => now()->subDay()]);
    Recipe::factory()->public()->create(['title' => 'C', 'prep_minutes' => null, 'cook_minutes' => null, 'saves_count' => 5, 'rating_avg' => 4, 'rating_count' => 1, 'published_at' => now()]);

    expect(titles(new RecipeFilters(sort: 'newest')))->toBe(['C', 'A', 'B'])
        ->and(titles(new RecipeFilters(sort: 'top-rated')))->toBe(['B', 'C', 'A'])
        ->and(titles(new RecipeFilters(sort: 'most-saved')))->toBe(['A', 'C', 'B'])
        ->and(titles(new RecipeFilters(sort: 'quickest')))->toBe(['A', 'B', 'C'])
        ->and(titles(new RecipeFilters(sort: 'a-z')))->toBe(['A', 'B', 'C']);
});

it('ignores unknown values instead of failing', function () {
    Recipe::factory()->public()->count(2)->create();

    $filters = new RecipeFilters(meal: 'second-breakfast', cuisine: 'martian', time: '5', rating: '9', collections: ['nope', 7], diets: [['x']], sort: 'loudest');

    expect($filters->count())->toBe(0)
        ->and($filters->sort)->toBe(RecipeSort::Newest)
        ->and(titles($filters))->toHaveCount(2);
});

it('counts the filters that are on', function () {
    expect((new RecipeFilters(meal: 'dinner', rating: 4, collections: ['one-pot', 'one-pot'], diets: ['vegan']))->count())->toBe(4);
});
