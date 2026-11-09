<?php

use App\Enums\Collection;
use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function editor(User $user, ?Recipe $recipe = null): Testable
{
    return Livewire::actingAs($user)->test('pages::recipes.edit', $recipe ? ['recipe' => $recipe] : []);
}

function fillRecipe(Testable $editor, array $overrides = []): Testable
{
    return $editor->set([
        'form.title' => 'Tomato Soup',
        'form.summary' => 'Cozy and quick.',
        'form.ingredients.0.quantity' => '2',
        'form.ingredients.0.unit' => 'cups',
        'form.ingredients.0.name' => 'tomatoes',
        'form.steps.0.body' => 'Simmer everything.',
        ...$overrides,
    ]);
}

it('shows the editor to members and sends guests to log in', function () {
    $this->get('/recipes/new')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get('/recipes/new')->assertOk()->assertSee('Write it <em>down</em>', false);
});

it('creates a complete recipe with its ingredients and steps', function () {
    $user = User::factory()->create();

    $editor = fillRecipe(editor($user))->call('save')->assertHasNoErrors();

    $recipe = Recipe::sole();
    $editor->assertRedirect(route('recipes.edit', $recipe));

    expect($recipe)
        ->title->toBe('Tomato Soup')
        ->user_id->toBe($user->id)
        ->status->toBe(RecipeStatus::Complete)
        ->visibility->toBe(Visibility::Private)
        ->and(strlen($recipe->share_token))->toBe(32)
        ->and($recipe->ingredients->sole()->only('quantity', 'unit', 'name', 'position'))->toBe(['quantity' => '2', 'unit' => 'cups', 'name' => 'tomatoes', 'position' => 1])
        ->and($recipe->steps->sole()->body)->toBe('Simmer everything.');
});

it('saves a draft without ingredients or steps', function () {
    $user = User::factory()->create();

    editor($user)->set('form.title', 'Idea for later')->call('save')->assertHasNoErrors();

    expect(Recipe::sole())->status->toBe(RecipeStatus::Draft);
});

it('ignores blank rows and keeps one to type into', function () {
    $user = User::factory()->create();

    $editor = editor($user)->set('form.title', 'Toast')->call('addIngredient')->call('addStep')->call('save')->assertHasNoErrors();

    expect(Recipe::sole()->ingredients)->toHaveCount(0);
});

it('validates the recipe', function (array $input, string $field) {
    fillRecipe(editor(User::factory()->create()), $input)->call('save')->assertHasErrors($field);

    expect(Recipe::count())->toBe(0);
})->with([
    'missing title' => [['form.title' => ''], 'form.title'],
    'long title' => [['form.title' => str_repeat('a', 81)], 'form.title'],
    'long summary' => [['form.summary' => str_repeat('a', 281)], 'form.summary'],
    'ingredient without a name' => [['form.ingredients.0.name' => '', 'form.ingredients.0.quantity' => '2'], 'form.ingredients.0.name'],
    'unknown meal' => [['form.meal_type' => 'Second breakfast'], 'form.meal_type'],
    'unknown collection' => [['form.collections' => ['Mystery']], 'form.collections.0'],
    'negative minutes' => [['form.prep_minutes' => -5], 'form.prep_minutes'],
    'zero servings' => [['form.servings' => 0], 'form.servings'],
    'video from elsewhere' => [['form.video_url' => 'https://example.com/video'], 'form.video_url'],
    'unknown visibility' => [['form.visibility' => 'friends'], 'form.visibility'],
]);

it('accepts YouTube and Vimeo links and embeds them safely', function (string $url, string $embed) {
    $user = User::factory()->create();

    fillRecipe(editor($user), ['form.video_url' => $url])->call('save')->assertHasNoErrors();

    expect(Recipe::sole()->videoEmbedUrl())->toBe($embed);
})->with([
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871'],
]);

it('edits a recipe, keeping, reordering and removing rows', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->complete()->for($user)->create(['title' => 'Old']);
    [$first, $second] = $recipe->ingredients->all();

    editor($user, $recipe)
        ->assertSet('form.title', 'Old')
        ->call('moveIngredient', $second->uuid, 0)
        ->call('removeStep', $recipe->steps->first()->uuid)
        ->set('form.title', 'New')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $recipe->refresh()->load('ingredients', 'steps');

    expect($recipe->title)->toBe('New')
        ->and($recipe->ingredients->pluck('uuid')->all())->toBe([$second->uuid, $first->uuid])
        ->and($recipe->ingredients->pluck('position')->all())->toBe([1, 2])
        ->and($recipe->steps)->toHaveCount(1)
        ->and($recipe->steps->first()->position)->toBe(1);
});

it('moves rows with the arrow buttons', function () {
    $user = User::factory()->create();
    $editor = editor($user)->call('addStep')->set('form.steps.0.body', 'First')->set('form.steps.1.body', 'Second');
    $key = $editor->get('form.steps')[1]['key'];

    $editor->call('moveStep', $key, 0);

    expect(collect($editor->get('form.steps'))->pluck('body')->all())->toBe(['Second', 'First']);
});

it('ticks Quick & Easy and Slow Cook when the details fit, and respects an untick', function () {
    $editor = editor(User::factory()->create())
        ->set('form.difficulty', 'Easy')
        ->set('form.cook_minutes', 15)
        ->assertDispatched('toast', type: 'info')
        ->set('form.prep_minutes', 10);

    expect($editor->get('form.collections'))->toBe([Collection::QuickAndEasy->value]);

    $editor->set('form.collections', [])->set('form.cook_minutes', 10);
    expect($editor->get('form.collections'))->toBe([]);

    $editor->set('form.method', 'Slow Cooker');
    expect($editor->get('form.collections'))->toBe([Collection::SlowCook->value]);
});

it('shares a complete recipe from a confirmed account', function (string $visibility) {
    $user = User::factory()->create();

    fillRecipe(editor($user), ['form.visibility' => $visibility])->call('save')->assertHasNoErrors();

    $recipe = Recipe::sole();
    expect($recipe->visibility->value)->toBe($visibility)
        ->and($recipe->published_at !== null)->toBe($visibility === 'public');
})->with(['public', 'unlisted']);

it('keeps unconfirmed accounts and incomplete recipes private', function () {
    fillRecipe(editor(User::factory()->unverified()->create()), ['form.visibility' => 'public'])
        ->call('save')
        ->assertHasErrors('form.visibility');

    editor(User::factory()->create())
        ->set(['form.title' => 'Just a title', 'form.visibility' => 'public'])
        ->call('save')
        ->assertHasErrors('form.visibility');

    expect(Recipe::count())->toBe(0);
});

it('stores a photo in three WebP sizes and replaces it later', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    fillRecipe(editor($user))->set('photo', UploadedFile::fake()->image('soup.jpg', 2000, 1500))->call('save')->assertHasNoErrors();

    $recipe = Recipe::sole();
    $base = $recipe->image_path;

    foreach ([400, 800, 1600] as $width) {
        Storage::disk('public')->assertExists("{$base}-{$width}.webp");
    }

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get("{$base}-800.webp"));
    expect([$width, $height])->toBe([800, 600])
        ->and($recipe->imageSrcset())->toContain('400w')->toContain('1600w');

    editor($user, $recipe)->set('photo', UploadedFile::fake()->image('new.png', 800, 600))->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertMissing("{$base}-800.webp");
    expect($recipe->fresh()->image_path)->not->toBe($base);
});

it('removes the photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    fillRecipe(editor($user))->set('photo', UploadedFile::fake()->image('soup.jpg', 800, 600))->call('save');
    $recipe = Recipe::sole();
    $base = $recipe->image_path;

    editor($user, $recipe)->set('removePhoto', true)->call('save');

    expect($recipe->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing("{$base}-400.webp");
});

it('rejects unsuitable photos', function (Closure $file) {
    Storage::fake('public');

    fillRecipe(editor(User::factory()->create()))->set('photo', $file())->call('save')->assertHasErrors('photo');

    expect(Recipe::count())->toBe(0);
})->with([
    'not an image' => fn () => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    'too small' => fn () => UploadedFile::fake()->image('tiny.png', 200, 150),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg', 800, 600)->size(9000),
]);

it("never lets anyone edit someone else's recipe", function () {
    $recipe = Recipe::factory()->public()->create();
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('recipes.edit', $recipe))->assertNotFound();
});

it('stops at the recipe limit', function () {
    config(['relay.max_recipes' => 1]);
    $user = User::factory()->create();
    Recipe::factory()->for($user)->create();

    $this->actingAs($user)->get('/recipes/new')->assertForbidden();
});

it('gives the demo account a smaller limit', function () {
    $user = User::factory()->demo()->create();

    expect($user->recipeLimit())->toBe(25);
});

it('resets the share link', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->unlisted()->for($user)->create();
    $old = $recipe->share_token;

    editor($user, $recipe)->assertSee($recipe->shareUrl())->call('resetShareLink')->assertDispatched('toast', type: 'success');

    expect($recipe->fresh()->share_token)->not->toBe($old)->toHaveLength(32);
});

it('deletes the recipe with its photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    fillRecipe(editor($user))->set('photo', UploadedFile::fake()->image('soup.jpg', 800, 600))->call('save');
    $recipe = Recipe::sole();
    $base = $recipe->image_path;

    editor($user, $recipe)->call('delete')->assertRedirect(route('my.recipes'));

    expect(Recipe::count())->toBe(0);
    Storage::disk('public')->assertMissing("{$base}-1600.webp");
});
