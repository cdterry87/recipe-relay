<?php

use App\Enums\Collection;
use App\Models\Recipe;
use App\Models\User;

it('shows a public recipe to everyone with structured data', function () {
    $recipe = Recipe::factory()->public()->create(['title' => 'Tomato Soup', 'collections' => [Collection::QuickAndEasy]]);

    $this->get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertSee('<h1 class="recipe__title">Tomato Soup</h1>', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Recipe"', false)
        ->assertSee('<link rel="canonical" href="'.route('recipes.show', $recipe).'">', false)
        ->assertSee('Quick &amp; Easy', false)
        ->assertDontSee('noindex');
});

it('hides private and unlisted recipes from everyone but the owner', function (string $state) {
    $recipe = $state === 'private' ? Recipe::factory()->complete()->create() : Recipe::factory()->unlisted()->create();

    $this->get(route('recipes.show', $recipe))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('recipes.show', $recipe))->assertNotFound();

    $this->actingAs($recipe->user)
        ->get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertSee('Edit recipe')
        ->assertSee('noindex')
        ->assertDontSee('application/ld+json', false);
})->with(['private', 'unlisted']);

it('opens unlisted recipes through the share link only', function () {
    $recipe = Recipe::factory()->unlisted()->create(['title' => 'Secret Stew']);

    $this->get(route('recipes.shared', $recipe->share_token))
        ->assertOk()
        ->assertSee('Secret Stew')
        ->assertSee('noindex');

    $this->get('/r/'.str_repeat('x', 32))->assertNotFound();
});

it('keeps private recipes closed even with the share link', function () {
    $recipe = Recipe::factory()->complete()->create();

    $this->get(route('recipes.shared', $recipe->share_token))->assertNotFound();
});

it('never resolves a recipe by its numeric id', function () {
    $recipe = Recipe::factory()->public()->create();

    $this->get('/recipes/'.$recipe->id)->assertNotFound();
});

it('shows ingredients, steps, facts and the servings scaler', function () {
    $recipe = Recipe::factory()->public()->create(['servings' => 4, 'prep_minutes' => 15, 'cook_minutes' => 60, 'notes' => "Line one\nLine two"]);

    $this->get(route('recipes.show', $recipe))
        ->assertSee('recipePage(4)', false)
        ->assertSee('1h 15m')
        ->assertSee('Line one<br />', false)
        ->assertSee($recipe->ingredients->first()->name)
        ->assertSee($recipe->steps->first()->body);
});

it('escapes everything the cook typed', function () {
    $recipe = Recipe::factory()->public()->create(['title' => '<script>alert(1)</script>', 'notes' => '<img src=x onerror=alert(1)>']);

    $this->get(route('recipes.show', $recipe))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x', false);
});

it('embeds only allow-listed video links', function () {
    $recipe = Recipe::factory()->public()->create(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

    $this->get(route('recipes.show', $recipe))->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

    expect((new Recipe(['video_url' => 'javascript:alert(1)']))->videoEmbedUrl())->toBeNull();
});

it('suggests more public recipes from the creator and similar ones', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->public()->for($user)->create();
    $more = Recipe::factory()->public()->for($user)->create(['title' => 'Another One']);
    $hidden = Recipe::factory()->unlisted()->for($user)->create(['title' => 'Hidden One']);
    $similar = Recipe::factory()->public()->create(['title' => 'Similar One']);

    $this->get(route('recipes.show', $recipe))
        ->assertSee('Another One')
        ->assertSee('Similar One')
        ->assertDontSee('Hidden One');
});

it('lists my recipes, newest first, with status badges', function () {
    $user = User::factory()->create();
    Recipe::factory()->for($user)->create(['title' => 'My Draft']);
    Recipe::factory()->public()->for($user)->create(['title' => 'My Public']);
    Recipe::factory()->public()->create(['title' => 'Not Mine']);

    $this->actingAs($user)->get(route('my.recipes'))
        ->assertOk()
        ->assertSee('My Draft')
        ->assertSee('My Public')
        ->assertDontSee('Not Mine')
        ->assertSee('Draft')
        ->assertSee('2 recipes');
});

it('shows an empty state with no recipes', function () {
    $this->actingAs(User::factory()->create())->get(route('my.recipes'))->assertSee('Write your first recipe');
});

it('loads more recipes on request', function () {
    $user = User::factory()->create();
    Recipe::factory()->for($user)->count(25)->create();

    Livewire\Livewire::actingAs($user)->test('pages::my.recipes')
        ->assertSee('Show more')
        ->call('loadMore')
        ->assertDontSee('Show more');
});

it('has no image addresses without a photo', function () {
    $recipe = Recipe::factory()->create();

    expect($recipe->imageUrl())->toBeNull()
        ->and($recipe->imageSrcset())->toBeNull()
        ->and($recipe->totalMinutes())->toBe(30)
        ->and(Recipe::formatMinutes(null))->toBeNull()
        ->and(Recipe::formatMinutes(120))->toBe('2h');
});
