<?php

use App\Models\Recipe;
use App\Models\User;
use App\Support\Showcase;

it('shows real trending recipes once there are enough', function () {
    Recipe::factory()->public()->count(3)->create();

    $this->get(route('home'))->assertSee(Showcase::recipes()[0]['title']);

    Recipe::factory()->public()->create(['title' => 'Real Recipe Four', 'saves_count' => 99]);

    $this->get(route('home'))
        ->assertSee('Real Recipe Four')
        ->assertDontSee(Showcase::recipes()[0]['title'])
        ->assertSee('New this <em>week</em>', false);
});

it('has a search box and meal tiles that lead to Discover', function () {
    $this->get(route('home'))
        ->assertSee('action="'.route('discover').'"', false)
        ->assertSee('name="q"', false)
        ->assertSee(route('discover', ['meal' => 'breakfast']))
        ->assertSee(route('collections.show', 'slow-cook'));
});

it('shows trending recipes with photos and a link to discover more', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('Trending <em>recipes</em>', false)
        ->assertSee(e(route('discover', ['sort' => 'most-saved'])), false)
        ->assertSee('View more');

    foreach (Showcase::recipes() as $recipe) {
        $response->assertSee($recipe['title'])
            ->assertSee(asset('images/samples/'.$recipe['image'].'.webp'));

        expect(public_path('images/samples/'.$recipe['image'].'.webp'))->toBeFile();
    }
});

it('shows trending creators with a link to see more', function () {
    $response = $this->get(route('home'))
        ->assertSee('Trending <em>creators</em>', false)
        ->assertSee('href="'.route('creators').'"', false);

    foreach (Showcase::creators() as $creator) {
        $response->assertSee($creator['name']);
    }
});

it('describes the core features', function () {
    $this->get(route('home'))
        ->assertSee('From your kitchen to <em>theirs</em>', false)
        ->assertSee('Your recipes, your rules')
        ->assertSee('Find and save favorites')
        ->assertSee('Follow and pass it on');
});

it('formats durations the way cards show them', function (int $minutes, string $expected) {
    expect(Showcase::duration($minutes))->toBe($expected);
})->with([
    [15, '15 min'],
    [60, '1h'],
    [75, '1h 15m'],
]);

it('shows a placeholder page for creators', function () {
    $this->get(route('creators'))
        ->assertOk()
        ->assertSee('Trending <em>creators</em>', false)
        ->assertSee('Coming soon');
});

it('shows account links in the footer for guests and members', function () {
    $this->get(route('home'))
        ->assertSee('Your kitchen')
        ->assertSee('href="'.route('login').'">Log in</a>', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('href="'.route('settings.profile').'">Profile settings</a>', false)
        ->assertDontSee('>Log in</a>', false);
});
