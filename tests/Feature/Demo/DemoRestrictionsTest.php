<?php

use App\Actions\SaveRecipe;
use App\Enums\Visibility;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RecipeImages;
use App\Support\SeedPhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('keeps the demo account out of account settings', function () {
    $demo = User::factory()->demo()->create();

    $this->actingAs($demo)->get(route('settings.account'))
        ->assertRedirect(route('settings.profile'))
        ->assertSessionHas('toast.message', "That's turned off for the demo account.");

    $this->get(route('settings.profile'))->assertDontSee(route('settings.account'));
});

it('lets regular members into account settings', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.account'))->assertOk();
});

it('blocks the demo account from uploading files', function () {
    $this->actingAs(User::factory()->demo()->create())
        ->postJson(URL::signedRoute('livewire.upload-file'), ['files' => [UploadedFile::fake()->image('a.jpg')]])
        ->assertForbidden();
});

it('keeps every demo recipe private, whatever is asked for', function () {
    $demo = User::factory()->demo()->create();

    $recipe = app(SaveRecipe::class)->handle(
        $demo, null, ['title' => 'Pie'], [['name' => 'Flour']], [['body' => 'Bake.']],
        Visibility::Public, UploadedFile::fake()->image('pie.jpg', 800, 600),
    );

    expect($recipe->visibility)->toBe(Visibility::Private)
        ->and($recipe->image_path)->toBeNull()
        ->and($recipe->published_at)->toBeNull();
});

it('explains private demo recipes and offers the photo picker in the editor', function () {
    $demo = User::factory()->demo()->create();

    Livewire::actingAs($demo)->test('pages::recipes.edit')
        ->assertSee('Demo recipes stay private')
        ->assertDontSee('Anyone with the link')
        ->assertSee("can't upload files", false)
        ->assertDontSee('type="file"', false)
        ->set('form.title', 'Picked Pie')
        ->set('form.visibility', 'public')
        ->set('form.ingredients.0.name', 'Flour')
        ->set('form.steps.0.body', 'Bake.')
        ->set('seedPhoto', 'lasagna')
        ->call('save')
        ->assertHasNoErrors();

    $recipe = $demo->recipes()->firstOrFail();

    expect($recipe->visibility)->toBe(Visibility::Private)
        ->and($recipe->image_path)->toBe('seed/lasagna')
        ->and($recipe->imageUrl(800))->toBe(asset('images/seed/lasagna-800.webp'));
});

it('rejects photos that are not in the gallery', function () {
    Livewire::actingAs(User::factory()->demo()->create())->test('pages::recipes.edit')
        ->set('form.title', 'Pie')
        ->set('seedPhoto', '../../etc/passwd')
        ->call('save')
        ->assertHasErrors('seedPhoto');
});

it('only lets the demo account pick seed photos', function () {
    $member = User::factory()->create();

    Livewire::actingAs($member)->test('pages::recipes.edit')
        ->assertSee('type="file"', false)
        ->set('form.title', 'Pie')
        ->set('seedPhoto', 'lasagna')
        ->call('save');

    expect($member->recipes()->first()->image_path)->toBeNull();
});

it('lets the demo account pick an avatar from the gallery', function () {
    $demo = User::factory()->demo()->create();

    Livewire::actingAs($demo)->test('pages::settings.profile')
        ->assertSee("can't upload files", false)
        ->set('seedPhoto', 'pancakes')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($demo->fresh()->avatar_path)->toBe('seed/pancakes')
        ->and($demo->fresh()->avatarUrl())->toBe(asset('images/seed/pancakes-400.webp'));

    Livewire::actingAs($demo)->test('pages::settings.profile')->set('seedPhoto', 'nope')->assertHasErrors('seedPhoto');
    Livewire::actingAs(User::factory()->create())->test('pages::settings.profile')->set('seedPhoto', 'pancakes')->assertForbidden();
});

it('never deletes the shared seed photos', function () {
    $file = public_path('images/seed/lasagna-800.webp');
    Storage::fake('public');
    $recipe = Recipe::factory()->create(['image_path' => 'seed/lasagna']);

    RecipeImages::delete($recipe->image_path);
    $recipe->delete();

    expect($file)->toBeFile()
        ->and(SeedPhotos::isSeed('seed/lasagna'))->toBeTrue()
        ->and(SeedPhotos::isSeed('recipes/abc'))->toBeFalse();
});
