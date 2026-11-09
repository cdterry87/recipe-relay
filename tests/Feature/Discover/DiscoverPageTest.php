<?php

use App\Enums\Collection;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

it('lists only public recipes', function () {
    Recipe::factory()->public()->create(['title' => 'Public Pie']);
    Recipe::factory()->unlisted()->create(['title' => 'Unlisted Udon']);
    Recipe::factory()->complete()->create(['title' => 'Private Pasta']);

    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('Public Pie')
        ->assertDontSee('Unlisted Udon')
        ->assertDontSee('Private Pasta')
        ->assertSee('1 recipe');
});

it('reads its filters from the URL', function () {
    Recipe::factory()->public()->create(['title' => 'Breakfast Burrito', 'meal_type' => 'Breakfast']);
    Recipe::factory()->public()->create(['title' => 'Dinner Pasta', 'meal_type' => 'Dinner']);

    $this->get(route('discover', ['meal' => 'breakfast', 'q' => 'burrito']))
        ->assertSee('Breakfast Burrito')
        ->assertDontSee('Dinner Pasta');
});

it('filters live and starts again from the top', function () {
    Recipe::factory()->public()->create(['title' => 'Vegan Chili', 'dietary_tags' => ['Vegan']]);
    Recipe::factory()->public()->create(['title' => 'Beef Chili']);

    Livewire::test('pages::discover')
        ->set('shown', 48)
        ->call('toggleDiet', 'vegan')
        ->assertSet('diets', ['vegan'])
        ->assertSet('shown', 24)
        ->assertSee('Vegan Chili')
        ->assertDontSee('Beef Chili')
        ->call('toggleDiet', 'vegan')
        ->assertSee('Beef Chili')
        ->set('search', 'vegan')
        ->assertDontSee('Beef Chili');
});

it('clears every filter', function () {
    Livewire::test('pages::discover')
        ->set(['search' => 'x', 'meal' => 'dinner', 'collections' => ['one-pot'], 'sort' => 'a-z'])
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('meal', '')
        ->assertSet('collections', [])
        ->assertSet('sort', '');
});

it('shows more recipes on request', function () {
    Recipe::factory()->public()->count(26)->create();

    Livewire::test('pages::discover')
        ->assertSee('Show more')
        ->call('loadMore')
        ->assertSet('shown', 48)
        ->assertDontSee('Show more');
});

it('shows a friendly empty state', function () {
    Livewire::test('pages::discover')->set('search', 'nothing-here')->assertSee('No recipes <em>match</em>', false);
});

it('surprises you with a random recipe from the results', function () {
    $recipe = Recipe::factory()->public()->create(['meal_type' => 'Dessert']);
    Recipe::factory()->public()->create(['meal_type' => 'Dinner']);

    Livewire::test('pages::discover')->set('meal', 'dessert')->call('surprise')->assertRedirect(route('recipes.show', $recipe));

    Livewire::test('pages::discover')->set('search', 'no-such-thing')->call('surprise')->assertDispatched('toast', type: 'info')->assertNoRedirect();
});

it('only offers the sorts a page allows', function () {
    Recipe::factory()->public()->create(['title' => 'Zucchini', 'published_at' => now()]);
    Recipe::factory()->public()->create(['title' => 'Apple', 'published_at' => now()->subDay()]);

    // "updated" belongs to My recipes, so Discover falls back to newest first.
    $titles = Livewire::test('pages::discover')->set('sort', 'updated')->instance()->results->pluck('title')->all();

    expect($titles)->toBe(['Zucchini', 'Apple']);
});

it('shows a collection page with only its public recipes', function () {
    Recipe::factory()->public()->create(['title' => 'Quick One', 'collections' => [Collection::QuickAndEasy]]);
    Recipe::factory()->public()->create(['title' => 'Slow One', 'collections' => [Collection::SlowCook]]);
    Recipe::factory()->unlisted()->create(['title' => 'Hidden Quick', 'collections' => [Collection::QuickAndEasy]]);

    $this->get(route('collections.show', 'quick-and-easy'))
        ->assertOk()
        ->assertSee('<title>Quick &amp; Easy · '.config('app.name').'</title>', false)
        ->assertSee(Collection::QuickAndEasy->description())
        ->assertSee('Quick One')
        ->assertDontSee('Slow One')
        ->assertDontSee('Hidden Quick');

    $this->get('/collections/not-a-collection')->assertNotFound();
});

it('filters my recipes, drafts included', function () {
    $user = User::factory()->create();
    Recipe::factory()->for($user)->create(['title' => 'My Draft Soup']);
    Recipe::factory()->public()->for($user)->create(['title' => 'My Public Salad']);

    Livewire::actingAs($user)->test('pages::my.recipes')
        ->assertSee('My Draft Soup')
        ->set('search', 'soup')
        ->assertSee('My Draft Soup')
        ->assertDontSee('My Public Salad');
});

it('links recipe tags to filtered pages', function () {
    $recipe = Recipe::factory()->public()->create(['collections' => [Collection::OnePot], 'dietary_tags' => ['Vegan']]);

    $this->get(route('recipes.show', $recipe))
        ->assertSee(route('collections.show', 'one-pot'))
        ->assertSee(e(route('discover', ['diet' => ['vegan']])), false);
});

it('describes every collection and opens its page', function (Collection $collection) {
    expect($collection->description())->not->toBeEmpty()
        ->and($collection->emoji())->not->toBeEmpty()
        ->and(Collection::fromSlug($collection->slug()))->toBe($collection);

    $this->get(route('collections.show', $collection->slug()))->assertOk()->assertSee($collection->description());
})->with(Collection::cases());
