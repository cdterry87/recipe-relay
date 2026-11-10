<?php

use App\Actions\Cookbook;
use App\Actions\Follows;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\NewFollower;
use App\Notifications\RecipeRated;
use Illuminate\Support\Facades\Notification;

it('emails new followers and ratings only to members who opted in', function () {
    Notification::fake();
    $quiet = Recipe::factory()->public()->create()->user;
    $keen = Recipe::factory()->public()->create()->user;
    $keen->forceFill(['email_preferences' => ['new-follower' => true, 'new-rating' => true]])->save();
    $fan = User::factory()->create();

    foreach ([$quiet, $keen] as $creator) {
        app(Follows::class)->follow($fan, $creator);
        $recipe = $creator->recipes()->first();
        app(Cookbook::class)->save($fan, $recipe);
        app(Cookbook::class)->rate($fan, $recipe, 4);
    }

    Notification::assertSentTo($quiet, NewFollower::class, fn ($n, array $channels) => $channels === ['database']);
    Notification::assertSentTo($quiet, RecipeRated::class, fn ($n, array $channels) => $channels === ['database']);
    Notification::assertSentTo($keen, NewFollower::class, fn ($n, array $channels) => $channels === ['database', 'mail']);
    Notification::assertSentTo($keen, RecipeRated::class, fn ($n, array $channels) => $channels === ['database', 'mail']);
});

it('never emails the demo account, even when opted in', function () {
    $demo = User::factory()->demo()->create(['email_preferences' => ['new-follower' => true]]);

    expect((new NewFollower(User::factory()->create()))->via($demo))->toBe(['database']);
});

it('renders the follower and rating emails with unsubscribe links', function () {
    $creator = Recipe::factory()->public()->create(['title' => 'Lemon & Thyme Bars'])->user;
    $fan = User::factory()->create(['name' => 'Fan Person']);

    $follower = (new NewFollower($fan))->toMail($creator);
    $html = view($follower->view[0], $follower->viewData)->render();
    $text = view($follower->view[1], $follower->viewData)->render();

    expect($follower->subject)->toBe('Fan Person started following you')
        ->and($html)->toContain(route('creators.show', $fan))->toContain('/unsubscribe/'.$creator->uuid.'/new-follower')
        ->and($text)->toContain(route('creators.show', $fan));

    $rated = (new RecipeRated($fan, $creator->recipes()->first(), 4))->toMail($creator);
    $text = view($rated->view[1], $rated->viewData)->render();

    expect($rated->subject)->toBe('Fan Person rated Lemon & Thyme Bars')
        ->and($text)->toContain('Lemon & Thyme Bars')->toContain('4 out of 5');
});
