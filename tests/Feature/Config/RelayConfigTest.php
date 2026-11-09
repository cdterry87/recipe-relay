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
