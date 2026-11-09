<?php

use App\Models\User;
use Illuminate\Support\Str;

it('gives each record a uuid alongside its id', function () {
    $user = User::factory()->create();

    expect($user->id)->toBeInt()
        ->and(Str::isUuid($user->uuid))->toBeTrue()
        ->and($user->getKeyName())->toBe('id');
});

it('routes on the uuid', function () {
    $user = User::factory()->create();

    expect($user->getRouteKeyName())->toBe('uuid')
        ->and($user->getRouteKey())->toBe($user->uuid);
});

it('gives every record a different uuid', function () {
    [$first, $second] = User::factory()->count(2)->create();

    expect($first->uuid)->not->toBe($second->uuid);
});
