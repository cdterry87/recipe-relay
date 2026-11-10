<?php

use App\Actions\Cookbook;
use App\Actions\Follows;
use App\Enums\Visibility;
use App\Models\Follow;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use App\Support\DemoWorld;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function demoUser(): User
{
    return User::query()->where('is_demo', true)->firstOrFail();
}

it('builds the sample creators, seed recipes and demo account', function () {
    $this->artisan('demo:reset')
        ->expectsOutputToContain('Restored 4 sample creators and 21 seed recipes.')
        ->expectsOutputToContain('The demo account demo@example.test has been reset.')
        ->assertSuccessful();

    $demo = demoUser();

    expect(User::query()->where('is_sample', true)->count())->toBe(4)
        ->and(User::query()->where('is_sample', true)->whereNotNull('password')->count())->toBe(0)
        ->and(Recipe::query()->whereNotNull('seed_key')->count())->toBe(21)
        ->and($demo->recipes()->count())->toBe(5)
        ->and($demo->recipes()->where('visibility', '!=', Visibility::Private)->count())->toBe(0)
        ->and($demo->hasVerifiedEmail())->toBeTrue()
        ->and($demo->following()->count())->toBe(2)
        ->and($demo->saves()->count())->toBe(4)
        ->and($demo->ratings()->count())->toBe(3);

    // Every public recipe has ratings from the other three creators, so it shows an average.
    Recipe::query()->listed()->get()->each(fn (Recipe $recipe) => expect($recipe->rating_count)->toBeGreaterThanOrEqual(3));

    // Fresh content this week, for the home page, following feed and digest.
    expect(Recipe::query()->listed()->where('published_at', '>=', now()->subWeek())->count())->toBeGreaterThanOrEqual(4);
});

it('sends no notifications while building', function () {
    Notification::fake();

    DemoWorld::reset();

    Notification::assertNothingSent();
});

it('restores everything the demo user changed, keeping recipe ids', function () {
    DemoWorld::reset();
    $demo = demoUser();
    $lasagna = Recipe::query()->where('seed_key', 'lasagna')->firstOrFail();
    $tacos = Recipe::query()->where('seed_key', 'beef-tacos')->firstOrFail();

    $lasagna->forceFill(['title' => 'Vandalised', 'image_path' => null])->save();
    $lasagna->steps()->delete();
    Recipe::query()->where('seed_key', 'eggplant-parmesan')->delete();
    $extra = Recipe::factory()->for($demo)->create(['title' => 'Demo Scribble']);
    app(Cookbook::class)->remove($demo, $tacos);
    app(Follows::class)->unfollow($demo, User::query()->where('email', 'rosa@samples.invalid')->first());
    $demo->forceFill(['name' => 'Someone Else', 'bio' => 'Changed'])->save();

    DemoWorld::reset();
    $demo->refresh();
    $lasagna->refresh();

    expect($lasagna->title)->toBe('Lasagna')
        ->and($lasagna->image_path)->toBe('seed/lasagna')
        ->and($lasagna->steps()->count())->toBe(5)
        ->and(Recipe::query()->where('seed_key', 'eggplant-parmesan')->exists())->toBeTrue()
        ->and(Recipe::query()->whereKey($extra->id)->exists())->toBeFalse()
        ->and($demo->hasSaved($tacos))->toBeTrue()
        ->and($demo->following()->count())->toBe(2)
        ->and($demo->name)->toBe('Demo User')
        ->and($demo->bio)->toBe(DemoWorld::DEMO_BIO)
        ->and(Recipe::query()->where('seed_key', 'lasagna')->value('id'))->toBe($lasagna->id);
});

it('gives the same result every time', function () {
    DemoWorld::reset();
    $counts = fn () => [Recipe::count(), Save::count(), Rating::count(), Follow::count(), User::count(), Recipe::query()->sum('rating_count')];
    $first = $counts();

    DemoWorld::reset();

    expect($counts())->toBe($first);
});

it('keeps real accounts and their saves on seed recipes', function () {
    DemoWorld::reset();
    $member = User::factory()->create();
    $mine = Recipe::factory()->public()->for($member)->create();
    $ramen = Recipe::query()->where('seed_key', 'pork-ramen')->firstOrFail();
    app(Cookbook::class)->save($member, $ramen);
    app(Cookbook::class)->rate($member, $ramen, 1);
    app(Follows::class)->follow($member, $ramen->user);

    DemoWorld::reset();

    expect(Recipe::query()->whereKey($mine->id)->exists())->toBeTrue()
        ->and($member->hasSaved($ramen))->toBeTrue()
        ->and($member->ratingFor($ramen))->toBe(1)
        ->and($member->isFollowing($ramen->user))->toBeTrue()
        ->and($ramen->fresh()->saves_count)->toBe(5);
});

it('recounts real recipes the demo account had saved', function () {
    DemoWorld::reset();
    $real = Recipe::factory()->public()->create();
    app(Cookbook::class)->save(demoUser(), $real);
    expect($real->fresh()->saves_count)->toBe(1);

    DemoWorld::reset();

    expect($real->fresh()->saves_count)->toBe(0);
});

it('logs the demo account out everywhere', function () {
    DemoWorld::reset();
    $demo = demoUser();
    $token = $demo->remember_token;
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $demo->id, 'payload' => '', 'last_activity' => time()]);

    DemoWorld::reset();

    expect(demoUser()->remember_token)->not->toBe($token)
        ->and(DB::table('sessions')->where('user_id', $demo->id)->exists())->toBeFalse();
});

it('refuses to take over a regular account that uses the demo email', function () {
    User::factory()->create(['email' => 'demo@example.test']);

    $this->artisan('demo:reset')
        ->expectsOutputToContain('already uses demo@example.test')
        ->assertFailed();

    expect(Recipe::count())->toBe(0);
});

it('refuses to take over a regular account that uses a sample address', function () {
    User::factory()->create(['email' => 'rosa@samples.invalid']);

    expect(fn () => DemoWorld::reset())->toThrow(RuntimeException::class);
});

it('builds the sample content without a demo account when none is configured', function () {
    config(['relay.demo.email' => null]);

    $this->artisan('demo:reset')->expectsOutputToContain('there is no demo account')->assertSuccessful();

    expect(User::query()->where('is_demo', true)->exists())->toBeFalse()
        ->and(Recipe::count())->toBe(16);
});

it('seeds the database with the same content', function () {
    $this->seed();

    expect(Recipe::count())->toBe(21)->and(demoUser()->email)->toBe('demo@example.test');
});

it('schedules the nightly reset only in demo mode', function (bool $demoMode) {
    config(['relay.demo_mode' => $demoMode]);

    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->filtersPass(app()))->toBe($demoMode);
})->with([true, false]);

it('fills the home page with real content after a reset', function () {
    DemoWorld::reset();

    $this->get(route('home'))
        ->assertSee('Rosa Delgado')
        ->assertSee(asset('images/seed/pork-ramen-800.webp'))
        ->assertSee('Featured creator');
});
