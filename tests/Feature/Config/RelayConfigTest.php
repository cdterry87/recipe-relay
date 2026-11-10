<?php

use Illuminate\Validation\Rules\Password;

it('defaults demo mode to off', function () {
    expect(config('relay.demo_mode'))->toBeFalse();
});

it('reads the demo account from the environment', function () {
    expect(config('relay.demo'))->toMatchArray([
        'name' => 'Demo User',
        'email' => 'demo@example.test',
        'password' => 'demo-password',
        'timezone' => 'UTC',
    ]);
});

it('uses one password rule of at least 8 characters', function () {
    $rule = Password::defaults();

    expect(validator(['password' => 'short'], ['password' => $rule])->fails())->toBeTrue()
        ->and(validator(['password' => 'long enough'], ['password' => $rule])->passes())->toBeTrue();
});

it('marks the session cookie secure when the app is served over https', function (string $url, bool $secure) {
    $original = getenv('APP_URL');
    putenv("APP_URL={$url}");
    $_ENV['APP_URL'] = $_SERVER['APP_URL'] = $url;

    try {
        $config = require config_path('session.php');
    } finally {
        putenv($original === false ? 'APP_URL' : "APP_URL={$original}");
        $_ENV['APP_URL'] = $_SERVER['APP_URL'] = $original === false ? null : $original;
    }

    expect($config['secure'])->toBe($secure);
})->with([
    ['https://recipes.example.com', true],
    ['http://localhost:8003', false],
]);
