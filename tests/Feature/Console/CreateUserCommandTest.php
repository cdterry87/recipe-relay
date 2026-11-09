<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a confirmed account from the answers to its questions', function () {
    config(['relay.demo_mode' => true]);

    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Chase')
        ->expectsQuestion('Email', 'chase@example.com')
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->expectsQuestion('Timezone', 'America/Chicago')
        ->expectsOutputToContain('Created chase@example.com.')
        ->assertSuccessful();

    $user = User::sole();

    expect($user)
        ->name->toBe('Chase')
        ->email->toBe('chase@example.com')
        ->timezone->toBe('America/Chicago')
        ->is_demo->toBeFalse()
        ->hasVerifiedEmail()->toBeTrue()
        ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
});

it('defaults the timezone to UTC', function () {
    $this->artisan('user:create', ['--name' => 'Chase', '--email' => 'chase@example.com'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->expectsQuestion('Timezone', '')
        ->assertSuccessful();

    expect(User::sole()->timezone)->toBe('UTC');
});

it('takes everything but the password from options', function () {
    $this->artisan('user:create', ['--name' => 'Chase', '--email' => 'chase@example.com', '--timezone' => 'Europe/Paris'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::sole()->timezone)->toBe('Europe/Paris');
});

it('refuses invalid options', function (array $options, string $error) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', ['--name' => 'Chase', '--email' => 'chase@example.com', '--timezone' => 'UTC', ...$options])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->expectsOutputToContain($error)
        ->assertFailed();

    expect(User::count())->toBe(1);
})->with([
    'taken email' => [['--email' => 'taken@example.com'], 'has already been taken'],
    'demo email' => [['--email' => 'demo@example.test'], 'reserved for the demo account'],
    'invalid email' => [['--email' => 'chase'], 'must be a valid email'],
    'unknown timezone' => [['--timezone' => 'Mars/Base'], 'must be a valid timezone'],
]);

it('refuses a mismatched password confirmation', function () {
    $this->artisan('user:create', ['--name' => 'Chase', '--email' => 'chase@example.com', '--timezone' => 'UTC'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'different-password')
        ->expectsOutputToContain('confirmation does not match')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuses to run without a terminal to ask for the password', function () {
    $this->artisan('user:create', ['--name' => 'Chase', '--email' => 'chase@example.com', '--no-interaction' => true])
        ->expectsOutputToContain('interactive terminal')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
