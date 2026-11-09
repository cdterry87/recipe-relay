<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('shows the profile settings page', function () {
    $user = User::factory()->create(['name' => 'Julia Child', 'bio' => 'Bon appétit!']);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertSee('Profile settings')
        ->assertSee('aria-current="page"', false);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->assertSet('name', 'Julia Child')
        ->assertSet('bio', 'Bon appétit!')
        ->assertSet('timezone', 'UTC');

    $this->get('/settings')->assertRedirect('/settings/profile');
});

it('saves the name, bio and timezone', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', '  Julia Child ')
        ->set('bio', 'I cook with butter.')
        ->set('timezone', 'America/Chicago')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    expect($user->fresh())
        ->name->toBe('Julia Child')
        ->bio->toBe('I cook with butter.')
        ->timezone->toBe('America/Chicago');
});

it('clears an empty bio', function () {
    $user = User::factory()->create(['bio' => 'Old bio']);

    Livewire::actingAs($user)->test('pages::settings.profile')->set('bio', '')->call('save');

    expect($user->fresh()->bio)->toBeNull();
});

it('validates the profile', function (array $input, string $field) {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::settings.profile')
        ->set($input)
        ->call('save')
        ->assertHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'long bio' => [['bio' => str_repeat('a', 281)], 'bio'],
    'unknown timezone' => [['timezone' => 'Mars/Olympus_Mons'], 'timezone'],
]);

it('stores an uploaded photo as a square WebP', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->image('me.jpg', 800, 600))
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success')
        ->assertSet('photo', null);

    $path = $user->fresh()->avatar_path;

    expect($path)->toStartWith('avatars/'.$user->uuid.'-')->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect([$width, $height, $type])->toBe([400, 400, IMAGETYPE_WEBP]);
});

it('replaces the old photo file', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('pages::settings.profile');

    $component->set('photo', UploadedFile::fake()->image('one.png', 200, 200));
    $first = $user->fresh()->avatar_path;

    $component->set('photo', UploadedFile::fake()->image('two.png', 200, 200));

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
});

it('rejects files that are not suitable photos', function (Closure $file) {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('photo', $file())
        ->assertHasErrors('photo');

    expect($user->fresh()->avatar_path)->toBeNull();
})->with([
    'not an image' => fn () => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    'too small' => fn () => UploadedFile::fake()->image('tiny.png', 32, 32),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg', 200, 200)->size(6000),
]);

it('reports a photo that cannot be read', function () {
    $user = User::factory()->create();
    $broken = UploadedFile::fake()->createWithContent('broken.png', 'not really a png');

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('photo', $broken)
        ->assertHasErrors('photo');

    expect($user->fresh()->avatar_path)->toBeNull();
});

it('limits photo uploads', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('pages::settings.profile');

    foreach (range(1, 10) as $attempt) {
        $component->set('photo', UploadedFile::fake()->image("photo{$attempt}.png", 100, 100));
    }

    $component->set('photo', UploadedFile::fake()->image('one-more.png', 100, 100))
        ->assertHasErrors('photo');
});

it('removes the photo', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('pages::settings.profile');
    $component->set('photo', UploadedFile::fake()->image('me.png', 200, 200));
    $path = $user->fresh()->avatar_path;

    $component->call('removePhoto')->assertDispatched('toast', type: 'success');

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('shows initials when there is no photo', function () {
    $user = User::factory()->create(['name' => 'Julia  Carolyn Child']);

    expect($user->initials())->toBe('JC')
        ->and($user->avatarUrl())->toBeNull();

    $this->actingAs($user)->get('/settings/profile')->assertSee('avatar--initials', false);
});
