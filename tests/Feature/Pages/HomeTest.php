<?php

use App\Actions\Cookbook;
use App\Actions\Follows;
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
            ->assertSee(asset('images/seed/'.$recipe['image'].'-800.webp'));

        expect(public_path('images/seed/'.$recipe['image'].'-800.webp'))->toBeFile();
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

it('shows the creators page', function () {
    $this->get(route('creators'))
        ->assertOk()
        ->assertSee('Meet the <em>creators</em>', false);
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

it('shows members recipes from people they follow and their recent saves', function () {
    $member = User::factory()->create();
    $creator = Recipe::factory()->public()->create(['title' => 'Followed Fritters'])->user;
    app(Follows::class)->follow($member, $creator);
    app(Cookbook::class)->save($member, Recipe::factory()->unlisted()->create(['title' => 'Saved Soup']));

    $this->actingAs($member)->get(route('home'))
        ->assertSee('From people you <em>follow</em>', false)
        ->assertSee('Followed Fritters')
        ->assertSee('Recently <em>saved</em>', false)
        ->assertSee('Saved Soup');

    $this->post(route('logout'));
    $this->get(route('home'))->assertDontSee('Recently <em>saved</em>', false);
});

it('switches trending creators to real ones once there are four', function () {
    $creators = Recipe::factory()->public()->count(4)->create()->pluck('user');

    $this->get(route('home'))
        ->assertSee('href="'.route('creators.show', $creators->first()).'"', false)
        ->assertDontSee('Weeknight dinners');
});

it('features the most followed creator who posted this month', function () {
    $star = Recipe::factory()->public()->create()->user;
    $star->forceFill(['bio' => 'Bakes every Sunday.'])->save();
    $old = Recipe::factory()->public()->create(['published_at' => now()->subMonths(2)])->user;
    User::factory()->count(2)->create()->each(fn (User $fan) => app(Follows::class)->follow($fan, $old));
    app(Follows::class)->follow(User::factory()->create(), $star);

    $this->get(route('home'))
        ->assertSee('Featured creator')
        ->assertSee('Bakes every Sunday.');
});
