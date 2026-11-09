<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function passTurnstile(): void
{
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
}

function registration(array $overrides = []): Testable
{
    return Livewire::test('pages::auth.register')->set([
        'name' => 'Julia Child',
        'email' => 'julia@example.com',
        'password' => 'bon-appetit',
        'password_confirmation' => 'bon-appetit',
        'timezone' => 'America/Chicago',
        'turnstile' => 'token',
        ...$overrides,
    ]);
}

it('shows the registration page with the Turnstile widget', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('<title>Create an account · '.config('app.name').'</title>', false)
        ->assertSee("x-data=\"turnstile('".config('relay.turnstile.site_key')."')\"", false);
});

it('does not load the Turnstile widget on other pages', function () {
    $this->get('/login')->assertDontSee('x-data="turnstile', false);
});

it('registers a member, logs them in, and asks them to confirm their email', function () {
    Notification::fake();
    passTurnstile();

    registration()
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $user = User::sole();

    expect($user)
        ->name->toBe('Julia Child')
        ->email->toBe('julia@example.com')
        ->timezone->toBe('America/Chicago')
        ->is_demo->toBeFalse()
        ->is_sample->toBeFalse()
        ->email_verified_at->toBeNull()
        ->and(Hash::check('bon-appetit', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('falls back to UTC for an unknown timezone', function (string $timezone) {
    passTurnstile();

    registration(['timezone' => $timezone])->call('register')->assertHasNoErrors();

    expect(User::sole()->timezone)->toBe('UTC');
})->with(['', 'Mars/Olympus_Mons']);

it('sends the token, secret, and IP to Cloudflare', function () {
    passTurnstile();

    registration(['turnstile' => 'abc123'])->call('register');

    Http::assertSent(fn (Request $request) => $request->url() === config('relay.turnstile.verify_url')
        && $request['secret'] === config('relay.turnstile.secret_key')
        && $request['response'] === 'abc123'
        && $request['remoteip'] === '127.0.0.1');
});

it('fails closed when Turnstile verification fails', function (Closure $response) {
    Http::fake(['challenges.cloudflare.com/*' => $response]);

    registration()
        ->call('register')
        ->assertHasErrors('turnstile')
        ->assertSet('turnstile', '')
        ->assertDispatched('turnstile-reset');

    expect(User::count())->toBe(0);
    $this->assertGuest();
})->with([
    'rejected token' => fn () => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]),
    'server error' => fn () => Http::response('Bad gateway', 502),
    'unreachable' => fn () => throw new ConnectionException('timeout'),
]);

it('rejects Cloudflare test keys in production', function () {
    passTurnstile();
    app()->detectEnvironment(fn () => 'production');

    registration()->call('register')->assertHasErrors('turnstile');

    Http::assertNothingSent();
});

it('requires a Turnstile token without calling Cloudflare', function () {
    Http::fake();

    registration(['turnstile' => ''])->call('register')->assertHasErrors('turnstile');

    Http::assertNothingSent();
});

it('resets the widget and passwords when other fields fail', function () {
    passTurnstile();

    registration(['email' => 'not-an-email'])
        ->call('register')
        ->assertHasErrors('email')
        ->assertSet('password', '')
        ->assertSet('turnstile', '')
        ->assertDispatched('turnstile-reset');
});

it('validates the registration form', function (array $input, string $field) {
    passTurnstile();

    registration($input)->call('register')->assertHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 256)], 'name'],
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'julia'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'mismatched confirmation' => [['password_confirmation' => 'different'], 'password'],
]);

it('rejects an email that is already registered', function () {
    passTurnstile();
    User::factory()->create(['email' => 'julia@example.com']);

    registration()->call('register')->assertHasErrors('email');
});

it('limits registration attempts per IP', function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

    foreach (range(1, 5) as $attempt) {
        registration()->call('register');
    }

    passTurnstile();

    registration()->call('register')->assertHasErrors('email')->assertNoRedirect();

    expect(User::count())->toBe(0);
});

it('closes registration in demo mode', function () {
    config(['relay.demo_mode' => true]);

    $this->get('/register')->assertNotFound();
    registration()->call('register')->assertStatus(404);

    expect(User::count())->toBe(0);
});

it('hides sign-up links in demo mode', function () {
    config(['relay.demo_mode' => true]);

    $this->get('/')->assertDontSee('Sign up');
});

it('shows sign-up links while registration is open', function () {
    $this->get('/')->assertSee('href="'.route('register').'"', false);
});
