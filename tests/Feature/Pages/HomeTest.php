<?php

use App\Models\User;
use App\Support\Showcase;

it('shows trending recipes with photos and a link to discover more', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('Trending <em>recipes</em>', false)
        ->assertSee('href="'.route('discover').'"', false)
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
        ->assertSee('Every recipe you love, in one <em>place</em>', false)
        ->assertSee('Your recipes, your rules')
        ->assertSee('Find and save favorites')
        ->assertSee('Follow and relay');
});

it('formats durations the way cards show them', function (int $minutes, string $expected) {
    expect(Showcase::duration($minutes))->toBe($expected);
})->with([
    [15, '15 min'],
    [60, '1h'],
    [75, '1h 15m'],
]);

it('shows placeholder pages for discover and creators', function (string $route, string $heading) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee($heading, false)
        ->assertSee('Coming soon');
})->with([
    ['discover', 'Discover <em>recipes</em>'],
    ['creators', 'Trending <em>creators</em>'],
]);

it('shows account links in the footer for guests and members', function () {
    $this->get(route('home'))
        ->assertSee('Your kitchen')
        ->assertSee('href="'.route('login').'">Log in</a>', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('href="'.route('settings.profile').'">Profile settings</a>', false)
        ->assertDontSee('>Log in</a>', false);
});
