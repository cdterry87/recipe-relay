<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

beforeEach(function () {
    config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'client-secret']);
});

function googleReturns(array $overrides = []): void
{
    $data = [
        'id' => '1234567890',
        'name' => 'Julia Child',
        'email' => 'julia@example.com',
        'email_verified' => true,
        ...$overrides,
    ];

    $googleUser = (new GoogleUser)->setRaw($data)->map([
        'id' => $data['id'],
        'name' => $data['name'],
        'email' => $data['email'],
    ]);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('shows the Google button only when it is configured', function () {
    $this->get('/login')->assertSee('Continue with Google');

    config(['services.google.client_id' => null]);

    $this->get('/login')->assertDontSee('Continue with Google');
    $this->get('/auth/google/redirect')->assertNotFound();
    $this->get('/auth/google/callback')->assertNotFound();
});

it('sends the visitor to Google', function () {
    $this->get('/auth/google/redirect')->assertRedirectContains('accounts.google.com');
});

it('creates a confirmed, passwordless account while registration is open', function () {
    googleReturns();

    $this->get('/auth/google/callback')->assertRedirect(route('home'));

    $user = User::sole();

    expect($user)
        ->name->toBe('Julia Child')
        ->email->toBe('julia@example.com')
        ->google_id->toBe('1234567890')
        ->hasPassword()->toBeFalse()
        ->hasVerifiedEmail()->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('signs in an existing account by email and links it', function () {
    $user = User::factory()->unverified()->create(['email' => 'julia@example.com']);
    googleReturns();

    $this->get('/auth/google/callback')->assertRedirect(route('home'));

    $user->refresh();

    expect($user->google_id)->toBe('1234567890')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(User::count())->toBe(1);

    $this->assertAuthenticatedAs($user);
});

it('signs in by Google ID after the email changes', function () {
    $user = User::factory()->unverified()->create(['email' => 'new@example.com']);
    $user->forceFill(['google_id' => '1234567890'])->save();
    googleReturns(['email' => 'old@example.com']);

    $this->get('/auth/google/callback')->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    // Google confirmed a different address, so this one stays unconfirmed.
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('signs in existing accounts but creates none while registration is closed', function () {
    config(['relay.demo_mode' => true]);
    googleReturns();

    $this->get('/auth/google/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHas('toast.type', 'error');

    expect(User::count())->toBe(0);
    $this->assertGuest();

    $user = User::factory()->create(['email' => 'julia@example.com']);

    $this->get('/auth/google/callback')->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('refuses an email Google has not confirmed', function () {
    googleReturns(['email_verified' => false]);

    $this->get('/auth/google/callback')->assertRedirect(route('login'));

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

it('refuses the demo account and sample creators', function (string $state) {
    User::factory()->{$state}()->create(['email' => 'julia@example.com']);
    googleReturns();

    $this->get('/auth/google/callback')->assertRedirect(route('login'));

    $this->assertGuest();
})->with(['demo', 'sample']);

it('recovers when Google sign-in fails', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andThrow(new Exception('Invalid state'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHas('toast.message', "Google sign-in didn't work. Please try again.");
});

it('takes an unconfirmed account back from whoever set its password', function () {
    // Someone signed up with Julia's address before she did, and set their own password.
    $user = User::factory()->unverified()->create(['email' => 'julia@example.com', 'password' => 'squatter-password']);
    $token = $user->remember_token;
    DB::table('sessions')->insert(['id' => 'squatter', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    googleReturns();

    $this->get('/auth/google/callback')->assertRedirect(route('home'));

    $user->refresh();

    expect($user->hasPassword())->toBeFalse()
        ->and($user->remember_token)->not->toBe($token)
        ->and(DB::table('sessions')->where('id', 'squatter')->exists())->toBeFalse();
    $this->assertAuthenticatedAs($user);
});

it('keeps the password of a confirmed account it links to', function () {
    $user = User::factory()->create(['email' => 'julia@example.com', 'password' => 'my-own-password']);
    googleReturns();

    $this->get('/auth/google/callback')->assertRedirect(route('home'));

    expect($user->fresh()->hasPassword())->toBeTrue();
});
