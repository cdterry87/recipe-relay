<?php

use App\Models\Recipe;
use App\Models\User;
use App\Support\DemoWorld;
use Illuminate\Support\Facades\DB;

/**
 * Guards against N+1 queries creeping back in: with the full demo world loaded, every main
 * page stays within a small, fixed number of queries. (Lazy loading also throws outside
 * production; see AppServiceProvider.)
 */
it('keeps each page within its query budget', function (?string $who, Closure $url) {
    DemoWorld::reset();
    $demo = User::query()->where('is_demo', true)->firstOrFail();

    if ($who === 'demo') {
        $this->actingAs($demo);
    }

    $path = $url($demo);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->get($path)->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(20);
})->with([
    'home (guest)' => [null, fn () => '/'],
    'home (member)' => ['demo', fn () => '/'],
    'discover' => [null, fn () => route('discover')],
    'collection' => [null, fn () => route('collections.show', 'quick-and-easy')],
    'creators' => [null, fn () => route('creators')],
    'creator profile' => [null, fn () => route('creators.show', User::query()->where('is_sample', true)->firstOrFail())],
    'recipe (member)' => ['demo', fn () => route('recipes.show', Recipe::query()->listed()->firstOrFail())],
    'my recipes' => ['demo', fn () => route('my.recipes')],
    'cookbook' => ['demo', fn () => route('my.cookbook')],
    'following' => ['demo', fn () => route('my.following')],
    'notifications' => ['demo', fn () => route('my.notifications')],
    'digest preview' => ['demo', fn () => route('my.digest')],
    'editor' => ['demo', fn (User $demo) => route('recipes.edit', $demo->recipes()->firstOrFail())],
]);
