<?php

use App\Actions\Cookbook;
use App\Livewire\RecipeActions;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use App\Notifications\RecipeRated;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function actions(Recipe $recipe)
{
    return Livewire::test(RecipeActions::class, ['recipe' => $recipe]);
}

it('saves and unsaves a recipe, keeping the counter right', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();

    $this->actingAs($member);
    actions($recipe)->call('toggleSave')->assertSee('Saved')->assertDispatched('toast');

    expect($recipe->fresh()->saves_count)->toBe(1)
        ->and($member->hasSaved($recipe))->toBeTrue();

    actions($recipe)->call('toggleSave')->assertSee('Save to rate');

    expect($recipe->fresh()->saves_count)->toBe(0)
        ->and($member->hasSaved($recipe))->toBeFalse();
});

it('never counts a save twice', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();

    app(Cookbook::class)->save($member, $recipe);
    app(Cookbook::class)->save($member, $recipe);

    expect(Save::count())->toBe(1)->and($recipe->fresh()->saves_count)->toBe(1);
});

it('lets members save unlisted recipes too', function () {
    $recipe = Recipe::factory()->unlisted()->create();

    $this->actingAs(User::factory()->create());
    actions($recipe)->call('toggleSave');

    expect($recipe->fresh()->saves_count)->toBe(1);
});

it('does not let owners save their own recipes', function () {
    $recipe = Recipe::factory()->public()->create();

    $this->actingAs($recipe->user);
    actions($recipe)->assertDontSee('Save to rate')->call('toggleSave')->assertForbidden();

    expect(Save::count())->toBe(0);
});

it('does not let anyone save a private recipe or a draft', function () {
    $this->actingAs(User::factory()->create());

    actions(Recipe::factory()->complete()->create())->assertDontSee('Save to rate')->call('toggleSave')->assertNotFound();
    actions(Recipe::factory()->create())->call('toggleSave')->assertNotFound();

    expect(Save::count())->toBe(0);
});

it('rates only after saving, and recalculates the average', function () {
    Notification::fake();
    $recipe = Recipe::factory()->public()->create();
    [$ann, $bea] = User::factory()->count(2)->create();

    $this->actingAs($ann);
    actions($recipe)->call('rate', 5)->assertDispatched('toast', type: 'info', message: 'Save this recipe to your cookbook to rate it.');
    expect(Rating::count())->toBe(0);

    actions($recipe)->call('toggleSave')->call('rate', 5);
    $this->actingAs($bea);
    actions($recipe)->call('toggleSave')->call('rate', 2);

    $recipe->refresh();
    expect($recipe->rating_count)->toBe(2)->and($recipe->rating_avg)->toBe(3.5);

    actions($recipe)->call('rate', 4);
    expect($recipe->fresh()->rating_avg)->toBe(4.5)->and(Rating::count())->toBe(2);
});

it('ignores ratings outside one to five stars', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();
    app(Cookbook::class)->save($member, $recipe);

    $this->actingAs($member);
    actions($recipe)->call('rate', 9)->call('rate', 0);

    expect(Rating::count())->toBe(0);
    expect(fn () => app(Cookbook::class)->rate($member, $recipe, 6))->toThrow(InvalidArgumentException::class);
});

it('removes the rating when the recipe is unsaved, after warning about it', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();
    app(Cookbook::class)->save($member, $recipe);
    app(Cookbook::class)->rate($member, $recipe, 4);

    $this->actingAs($member);
    actions($recipe)
        ->assertSeeHtml('wire:confirm="Removing this recipe from your cookbook also removes your 4★ rating."')
        ->call('toggleSave');

    $recipe->refresh();
    expect(Rating::count())->toBe(0)
        ->and($recipe->rating_count)->toBe(0)
        ->and($recipe->rating_avg)->toBe(0.0)
        ->and($recipe->saves_count)->toBe(0);
});

it('tells the owner about a new rating once', function () {
    Notification::fake();
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();
    app(Cookbook::class)->save($member, $recipe);

    app(Cookbook::class)->rate($member, $recipe, 3);
    app(Cookbook::class)->rate($member, $recipe, 5);

    Notification::assertSentToTimes($recipe->user, RecipeRated::class, 1);
});

it('does not count being saved or rated as an edit', function () {
    $recipe = Recipe::factory()->public()->create(['updated_at' => now()->subWeek()]);
    $before = $recipe->fresh()->updated_at;

    app(Cookbook::class)->save($member = User::factory()->create(), $recipe);
    app(Cookbook::class)->rate($member, $recipe, 5);

    expect($recipe->fresh()->updated_at->equalTo($before))->toBeTrue();
});

it('shows New until a recipe has three ratings, then the average', function () {
    $recipe = Recipe::factory()->public()->create(['rating_count' => 2, 'rating_avg' => 5]);

    $this->get(route('recipes.show', $recipe))->assertSee('New')->assertDontSee('Rated 5.0 out of 5');

    $recipe->forceFill(['rating_count' => 23, 'rating_avg' => 4.56])->save();

    $this->get(route('recipes.show', $recipe))->assertSee('Rated 4.6 out of 5 from 23 ratings');
});

it('sends a guest to log in and finishes the save when they come back', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();

    $guest = actions($recipe)->call('toggleSave')->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe($guest->get('returnUrl'))
        ->and(session('pending.save'))->toBe($recipe->uuid);

    $this->actingAs($member);
    actions($recipe)->assertDispatched('toast', type: 'success', message: 'Saved to your cookbook. You can rate it now.');

    expect($member->hasSaved($recipe))->toBeTrue()->and(session()->has('pending.save'))->toBeFalse();
});

it('does not finish a pending save for a different recipe', function () {
    $recipe = Recipe::factory()->public()->create();
    $other = Recipe::factory()->public()->create();
    $member = User::factory()->create();

    session()->put('pending.save', $other->uuid);
    $this->actingAs($member);
    actions($recipe);

    expect($member->hasSaved($recipe))->toBeFalse()->and($member->hasSaved($other))->toBeFalse();
});

it('keeps database ids out of the component', function () {
    $recipe = Recipe::factory()->public()->create();
    $this->actingAs(User::factory()->create());

    $snapshot = json_encode(actions($recipe)->snapshot);

    expect($snapshot)->toContain($recipe->uuid)->not->toContain('"key":'.$recipe->id);
});
