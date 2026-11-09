<?php

use App\Actions\Follows;
use App\Actions\SaveRecipe;
use App\Enums\Visibility;
use App\Livewire\FollowButton;
use App\Models\Follow;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\NewFollower;
use App\Notifications\NewRecipeFromFollowed;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function creator(): User
{
    return Recipe::factory()->public()->create()->user;
}

it('follows and unfollows a creator', function () {
    $creator = creator();
    $member = User::factory()->create();

    $this->actingAs($member);
    Livewire::test(FollowButton::class, ['creator' => $creator])
        ->assertSee('Follow')
        ->call('toggle')
        ->assertSee('Following')
        ->assertDispatched('followed');

    expect($member->isFollowing($creator))->toBeTrue()
        ->and($creator->followers()->count())->toBe(1);

    Livewire::test(FollowButton::class, ['creator' => $creator])->call('toggle')->assertSee('Follow');

    expect($member->fresh()->isFollowing($creator))->toBeFalse();
});

it('gives each follow a UUID', function () {
    app(Follows::class)->follow(User::factory()->create(), creator());

    expect(Follow::first()->uuid)->toBeString()->toHaveLength(36);
});

it('does not let members follow themselves', function () {
    $creator = creator();

    $this->actingAs($creator);
    Livewire::test(FollowButton::class, ['creator' => $creator])
        ->assertDontSee('Follow')
        ->call('toggle')
        ->assertDispatched('toast', type: 'info', message: "You can't follow yourself.");

    expect(Follow::count())->toBe(0);
});

it('only lets members follow creators with a public recipe', function () {
    $cook = Recipe::factory()->unlisted()->create()->user;

    $this->actingAs(User::factory()->create());
    Livewire::test(FollowButton::class, ['creator' => $cook])->assertDontSee('Follow')->call('toggle');

    expect(Follow::count())->toBe(0);
});

it('tells the creator once, even after unfollowing and following again', function () {
    $creator = creator();
    $member = User::factory()->create();
    $follows = app(Follows::class);

    $follows->follow($member, $creator);
    $follows->follow($member, $creator);
    $follows->unfollow($member, $creator);
    $follows->follow($member, $creator);

    expect($creator->notifications()->where('type', 'new-follower')->count())->toBe(1);
});

it('sends a guest to log in and finishes the follow when they come back', function () {
    $creator = creator();
    $member = User::factory()->create();

    Livewire::test(FollowButton::class, ['creator' => $creator])->call('toggle')->assertRedirect(route('login'));

    $this->actingAs($member);
    Livewire::test(FollowButton::class, ['creator' => $creator])->assertSee('Following');

    expect($member->isFollowing($creator))->toBeTrue();
});

it('tells followers the first time a recipe goes public, and only then', function () {
    Notification::fake();
    $creator = creator();
    $fans = User::factory()->count(2)->create();
    $fans->each(fn (User $fan) => app(Follows::class)->follow($fan, $creator));
    Notification::fake();

    $save = fn (?Recipe $recipe, Visibility $visibility) => app(SaveRecipe::class)->handle(
        $creator, $recipe, ['title' => 'Pie'], [['name' => 'Flour']], [['body' => 'Bake.']], $visibility,
    );

    $recipe = $save(null, Visibility::Private);
    Notification::assertNothingSent();

    $recipe = $save($recipe, Visibility::Public);
    Notification::assertSentTo($fans, NewRecipeFromFollowed::class);
    expect($recipe->announced_at)->not->toBeNull();

    $save($save($recipe, Visibility::Private), Visibility::Public);
    Notification::assertSentToTimes($fans->first(), NewRecipeFromFollowed::class, 1);
});

it('does not announce unlisted recipes', function () {
    Notification::fake();
    $creator = creator();
    app(Follows::class)->follow(User::factory()->create(), $creator);
    Notification::fake();

    app(SaveRecipe::class)->handle($creator, null, ['title' => 'Pie'], [['name' => 'Flour']], [['body' => 'Bake.']], Visibility::Unlisted);

    Notification::assertNothingSent();
});

it('notifies the creator of a new follower in the database', function () {
    Notification::fake();
    $creator = creator();

    app(Follows::class)->follow($member = User::factory()->create(), $creator);

    Notification::assertSentTo($creator, NewFollower::class, fn (NewFollower $n) => $n->toArray($creator) === ['actor' => ['uuid' => $member->uuid, 'name' => $member->name]]);
});
