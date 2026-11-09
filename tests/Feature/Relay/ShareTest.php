<?php

use App\Livewire\EmailRecipe;
use App\Mail\RecipeShared;
use App\Models\Recipe;
use App\Models\User;
use App\Support\ShareLinks;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('offers a share sheet with every network on public recipes', function () {
    $recipe = Recipe::factory()->public()->create(['title' => 'Tomato Soup']);

    $this->get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertSee('Share <em>Tomato Soup</em>', false)
        ->assertSee('Copy link')
        ->assertSee('facebook.com/sharer', false)
        ->assertSee('x.com/intent/post', false)
        ->assertSee('wa.me', false)
        ->assertSee('reddit.com/submit', false)
        ->assertSee('mailto:', false)
        ->assertSee('Log in</a> to send this recipe', false);
});

it('builds encoded share links, with Pinterest only when there is a photo', function () {
    $recipe = Recipe::factory()->public()->create(['title' => 'Mac & Cheese']);

    $links = collect(ShareLinks::for($recipe))->keyBy('network');

    expect($links->keys()->all())->toBe(['email', 'facebook', 'x', 'whatsapp', 'reddit'])
        ->and($links['facebook']['href'])->toContain(rawurlencode(route('recipes.show', $recipe)))
        ->and($links['reddit']['href'])->toContain('title=Mac%20%26%20Cheese');

    $recipe->forceFill(['image_path' => 'recipes/abc'])->save();

    expect(collect(ShareLinks::for($recipe))->pluck('network'))->toContain('pinterest');
});

it('shares the secret link for unlisted recipes', function () {
    $recipe = Recipe::factory()->unlisted()->create();
    $secret = route('recipes.shared', $recipe->share_token);

    $this->get($secret)
        ->assertSee('value="'.$secret.'"', false)
        ->assertSee("you're sharing its private link", false);
});

it('explains to owners how to share a private recipe', function () {
    $recipe = Recipe::factory()->complete()->create();

    $this->actingAs($recipe->user)->get(route('recipes.show', $recipe))
        ->assertSee('This recipe is private')
        ->assertSee('Change visibility')
        ->assertDontSee('facebook.com/sharer', false);
});

it('emails a recipe to a friend', function () {
    Mail::fake();
    $recipe = Recipe::factory()->public()->create(['title' => 'Tomato Soup']);
    $member = User::factory()->create(['name' => 'Ann Cook']);

    Livewire::actingAs($member)->test(EmailRecipe::class, ['recipe' => $recipe])
        ->set('email', 'friend@example.com')
        ->set('note', 'Try this!')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSet('email', '');

    Mail::assertQueued(RecipeShared::class, function (RecipeShared $mail) use ($recipe) {
        return $mail->hasTo('friend@example.com') && $mail->recipe->is($recipe) && $mail->note === 'Try this!' && $mail->replyTo === [];
    });
});

it('renders the shared recipe email', function () {
    $recipe = Recipe::factory()->unlisted()->create(['title' => 'Secret Stew']);
    $mail = new RecipeShared($recipe, User::factory()->create(['name' => 'Ann Cook']), "We loved it\nso much");

    $mail->assertHasSubject('Ann Cook shared a recipe with you: Secret Stew')
        ->assertSeeInHtml('We loved it')
        ->assertSeeInHtml(route('recipes.shared', $recipe->share_token))
        ->assertSeeInText(route('recipes.shared', $recipe->share_token))
        ->assertSeeInText("don't keep your address");
});

it('validates the friend\'s address and the note', function () {
    Mail::fake();
    $recipe = Recipe::factory()->public()->create();

    Livewire::actingAs(User::factory()->create())->test(EmailRecipe::class, ['recipe' => $recipe])
        ->set('email', 'not-an-email')
        ->set('note', str_repeat('a', 301))
        ->call('send')
        ->assertHasErrors(['email', 'note']);

    Mail::assertNothingQueued();
});

it('limits members to five shared emails an hour', function () {
    Mail::fake();
    $recipe = Recipe::factory()->public()->create();
    $component = Livewire::actingAs(User::factory()->create())->test(EmailRecipe::class, ['recipe' => $recipe]);

    foreach (range(1, 5) as $i) {
        $component->set('email', "friend{$i}@example.com")->call('send')->assertHasNoErrors();
    }

    $component->set('email', 'one-more@example.com')->call('send')->assertHasErrors('email');
    Mail::assertQueuedCount(5);
});

it('blocks the demo account, unverified members and unshareable recipes', function (string $case) {
    Mail::fake();
    $recipe = $case === 'private' ? Recipe::factory()->complete()->create() : Recipe::factory()->public()->create();
    $user = match ($case) {
        'demo' => User::factory()->demo()->create(),
        'unverified' => User::factory()->unverified()->create(),
        default => User::factory()->create(),
    };

    Livewire::actingAs($user)->test(EmailRecipe::class, ['recipe' => $recipe])
        ->set('email', 'friend@example.com')
        ->call('send')
        ->assertHasErrors('email');

    Mail::assertNothingQueued();
})->with(['demo', 'unverified', 'private']);

it('lists email settings in the settings tabs', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.profile'))->assertSee(route('settings.notifications'));
});
