<?php

use App\Actions\Cookbook;
use App\Actions\Follows;
use App\Enums\EmailPreference;
use App\Livewire\EmailRecipe;
use App\Livewire\RecipeActions;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Unsubscribe;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * One place that checks members can't reach each other's private things: recipes, account
 * details, notifications and settings. Hidden records answer 404, never 403, so their
 * existence isn't revealed.
 */
function stranger(): User
{
    return User::factory()->create();
}

it('hides private and unlisted recipes, and drafts, from everyone but the owner', function () {
    $private = Recipe::factory()->complete()->create(['title' => 'Secret Private Pie']);
    $unlisted = Recipe::factory()->unlisted()->create(['title' => 'Secret Unlisted Stew']);
    $draft = Recipe::factory()->create(['title' => 'Secret Draft Dip']);

    foreach ([null, stranger()] as $viewer) {
        $viewer ? $this->actingAs($viewer) : auth()->logout();

        foreach ([$private, $unlisted, $draft] as $recipe) {
            $this->get(route('recipes.show', $recipe))->assertNotFound();
        }

        $this->get(route('recipes.shared', $private->share_token))->assertNotFound();
        $this->get(route('recipes.shared', $draft->share_token))->assertNotFound();

        foreach ([route('discover', ['q' => 'Secret']), route('creators.show', $private->user), route('creators.show', $unlisted->user), route('home')] as $page) {
            $this->get($page)->assertDontSee('Secret Private Pie')->assertDontSee('Secret Unlisted Stew')->assertDontSee('Secret Draft Dip');
        }
    }
});

it('keeps other members out of the editor and its actions', function () {
    $recipe = Recipe::factory()->public()->create(['title' => 'Not Yours']);
    $other = stranger();

    $this->actingAs($other)->get(route('recipes.edit', $recipe))->assertNotFound();
    Livewire::actingAs($other)->test('pages::recipes.edit', ['recipe' => $recipe])->assertNotFound();

    expect($recipe->fresh()->title)->toBe('Not Yours');
});

it('refuses tampered recipe and creator ids in Livewire components', function () {
    $mine = Recipe::factory()->complete()->create();
    $theirs = Recipe::factory()->complete()->create();

    Livewire::actingAs($mine->user)->test('pages::recipes.edit', ['recipe' => $mine])
        ->set('recipeUuid', $theirs->uuid);
})->throws(CannotUpdateLockedPropertyException::class);

it('locks the recipe in the save button and email form', function (string $component) {
    $recipe = Recipe::factory()->public()->create();

    Livewire::actingAs(stranger())->test($component, ['recipe' => $recipe])
        ->set('recipeUuid', Recipe::factory()->complete()->create()->uuid);
})->with([RecipeActions::class, EmailRecipe::class])
    ->throws(CannotUpdateLockedPropertyException::class);

it('lists only my own recipes, saves, follows and notifications', function () {
    $me = stranger();
    $other = stranger();
    $theirs = Recipe::factory()->complete()->for($other)->create(['title' => 'Their Private Recipe']);
    $public = Recipe::factory()->public()->create(['title' => 'Their Saved Recipe']);
    app(Cookbook::class)->save($other, $public);
    app(Follows::class)->follow($other, $public->user);
    app(Follows::class)->follow(stranger(), $other);

    $this->actingAs($me);

    $this->get(route('my.recipes'))->assertDontSee('Their Private Recipe');
    $this->get(route('my.cookbook'))->assertDontSee('Their Saved Recipe');
    $this->get(route('my.following'))->assertDontSee($public->user->name);
    $this->get(route('my.notifications'))->assertSee('All quiet');
    $this->get(route('my.digest'))->assertDontSee($theirs->title);

    expect($other->unreadNotifications()->count())->toBe(1);
});

it('never shows a member\'s email address to anyone else', function () {
    $creator = Recipe::factory()->public()->create()->user;
    $creator->forceFill(['email' => 'private.address@example.com'])->save();
    $recipe = $creator->recipes()->first();

    foreach ([route('home'), route('discover'), route('creators'), route('creators.show', $creator), route('recipes.show', $recipe)] as $page) {
        $this->get($page)->assertDontSee('private.address@example.com');
        $this->actingAs(stranger())->get($page)->assertDontSee('private.address@example.com');
        auth()->logout();
    }
});

it('keeps database ids off public pages', function () {
    $recipe = Recipe::factory()->public()->create();

    $this->get(route('recipes.show', $recipe))
        ->assertDontSee('"key":'.$recipe->id, false)
        ->assertDontSee('/recipes/'.$recipe->id, false);
    $this->get('/recipes/'.$recipe->id)->assertNotFound();
});

it('keeps settings and member pages behind login', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['settings.profile', 'settings.account', 'settings.notifications', 'my.recipes', 'my.cookbook', 'my.following', 'my.notifications', 'my.digest', 'recipes.create']);

it('lets only the owner unsubscribe with a link made for them', function () {
    $victim = stranger();
    $url = Unsubscribe::url(stranger(), EmailPreference::Digest);

    $this->post(str_replace(explode('/', parse_url($url, PHP_URL_PATH))[2], $victim->uuid, $url))->assertForbidden();

    expect($victim->fresh()->prefers(EmailPreference::Digest))->toBeTrue();
});
