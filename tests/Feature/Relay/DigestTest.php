<?php

use App\Actions\Follows;
use App\Http\Middleware\SecurityHeaders;
use App\Mail\WeeklyDigest;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Digest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * A member following a creator who published something this week.
 */
function digestWorld(array $member = []): array
{
    $creator = Recipe::factory()->public()->create(['title' => 'Fresh Focaccia', 'published_at' => now()->subDays(2)])->user;
    $user = User::factory()->create($member);
    app(Follows::class)->follow($user, $creator);

    return [$user, $creator];
}

beforeEach(fn () => $this->travelTo(Carbon::parse('2026-11-15 15:00', 'UTC'))); // A Sunday.

it('builds a digest of new recipes from followed creators, then trending ones', function () {
    [$user, $creator] = digestWorld();
    Recipe::factory()->public()->count(4)->for($creator)->create(['published_at' => now()->subDay()]);
    Recipe::factory()->public()->for($creator)->create(['title' => 'Too Old', 'published_at' => now()->subDays(9)]);
    Recipe::factory()->unlisted()->for($creator)->create(['title' => 'Unlisted Thing']);
    Recipe::factory()->public()->create(['title' => 'Popular Pie', 'saves_count' => 50, 'published_at' => now()->subDay()]);
    Recipe::factory()->public()->for($user)->create(['title' => 'My Own Dish', 'saves_count' => 99, 'published_at' => now()->subDay()]);

    $digest = Digest::for($user);

    expect($digest->isEmpty())->toBeFalse()
        ->and($digest->creators)->toHaveCount(1)
        ->and($digest->creators[0]['recipes'])->toHaveCount(3)
        ->and($digest->creators[0]['more'])->toBe(2)
        ->and($digest->recipeCount())->toBe(5)
        ->and($digest->subject())->toBe('Your weekly digest: 5 new recipes from cooks you follow')
        ->and($digest->trending->pluck('title')->all())->toBe(['Popular Pie']);
});

it('is empty when nobody followed has posted', function () {
    $user = User::factory()->create();
    app(Follows::class)->follow($user, Recipe::factory()->public()->create(['published_at' => now()->subMonth()])->user);

    expect(Digest::for($user)->isEmpty())->toBeTrue();
});

it('never repeats recipes from the last digest', function () {
    [$user] = digestWorld();
    $user->forceFill(['last_digest_at' => now()->subDay()])->save();

    expect(Digest::for($user)->isEmpty())->toBeTrue();
});

it('works out when the digest is due in the member\'s timezone', function () {
    $user = User::factory()->make(['timezone' => 'America/Chicago', 'digest_day' => 0, 'digest_hour' => 9]);

    expect($user->digestIsDue(Carbon::parse('2026-11-15 15:00', 'UTC')))->toBeTrue()   // 9 AM Sunday in Chicago.
        ->and($user->digestIsDue(Carbon::parse('2026-11-15 09:00', 'UTC')))->toBeFalse()
        ->and($user->digestIsDue(Carbon::parse('2026-11-16 15:00', 'UTC')))->toBeFalse();

    $user->last_digest_at = Carbon::parse('2026-11-13 15:00', 'UTC');
    expect($user->digestIsDue(Carbon::parse('2026-11-15 15:00', 'UTC')))->toBeFalse();
});

it('queues digests that are due, once, and records when', function () {
    Mail::fake();
    [$due] = digestWorld(['timezone' => 'America/Chicago']);
    [$notYet] = digestWorld(['timezone' => 'UTC']);

    $this->artisan('digest:send')->expectsOutput('Queued 1 digest.')->assertSuccessful();

    Mail::assertQueued(WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->hasTo($due->email));
    Mail::assertNotQueued(WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->hasTo($notYet->email));
    expect($due->fresh()->last_digest_at)->not->toBeNull();

    $this->artisan('digest:send')->expectsOutput('Queued 0 digests.');
    Mail::assertQueuedCount(1);
});

it('never emails the demo account, sample creators, the unverified or members who opted out', function (array $state) {
    Mail::fake();
    digestWorld(['timezone' => 'America/Chicago', ...$state]);

    $this->artisan('digest:send')->assertSuccessful();

    Mail::assertNothingQueued();
})->with([
    'demo' => [['is_demo' => true]],
    'sample' => [['is_sample' => true]],
    'unverified' => [['email_verified_at' => null]],
    'opted out' => [['email_preferences' => ['digest' => false]]],
]);

it('skips members with nothing new', function () {
    Mail::fake();
    $user = User::factory()->create(['timezone' => 'America/Chicago']);
    app(Follows::class)->follow($user, Recipe::factory()->public()->create(['published_at' => now()->subMonth()])->user);

    $this->artisan('digest:send')->expectsOutput('Queued 0 digests.');
    Mail::assertNothingQueued();
});

it('renders the digest with its recipes and unsubscribe headers', function () {
    [$user, $creator] = digestWorld();
    $digest = Digest::for($user);
    $mail = new WeeklyDigest($user, now(), $digest->since);

    $user->forceFill(['last_digest_at' => now()])->save();

    $mail->assertSeeInHtml($creator->name)
        ->assertSeeInHtml('Fresh Focaccia')
        ->assertSeeInHtml('unsubscribe')
        ->assertSeeInText('Fresh Focaccia')
        ->assertHasSubject('Your weekly digest: 1 new recipe from cooks you follow');

    $headers = $mail->headers()->text;
    expect($headers['List-Unsubscribe'])->toContain('/unsubscribe/'.$user->uuid.'/digest')
        ->and($headers['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click');
});

it('previews the next digest in the browser, even for the demo account', function () {
    [$user] = digestWorld(['is_demo' => true]);

    $this->actingAs($user)->get(route('my.digest'))
        ->assertOk()
        ->assertSee('never receives email')
        ->assertSee('Fresh Focaccia')
        ->assertSee('class="email-preview"', false)
        ->assertDontSee('<meta name="color-scheme" content="light">', false)
        ->assertSee('<h2 style="margin:0 0 16px;', false);
});

it('says when the preview would not be sent', function () {
    $this->actingAs(User::factory()->create())->get(route('my.digest'))->assertSee("won't be sent", false);
    $this->actingAs(User::factory()->create(['email_preferences' => ['digest' => false]]))->get(route('my.digest'))->assertSee('turned off');
});

it('tells members when their next digest goes out', function () {
    [$user] = digestWorld(['timezone' => 'America/Chicago', 'digest_day' => 3, 'digest_hour' => 18]);

    $this->actingAs($user)->get(route('my.digest'))->assertSee('Wednesday, November 18 at 6 PM (America/Chicago)');
});

it('lets a response choose its own security headers instead of doubling them', function () {
    $response = (new SecurityHeaders)->handle(
        Request::create('/'),
        fn () => response('ok')->header('X-Frame-Options', 'SAMEORIGIN'),
    );

    expect($response->headers->all('x-frame-options'))->toBe(['SAMEORIGIN'])
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});
