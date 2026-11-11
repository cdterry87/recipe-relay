<?php

use App\Models\User;
use App\Rules\MaxMegapixels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * A PNG that is only a header: it claims the given size but holds no pixel data, like a
 * "decompression bomb" or a corrupt file. getimagesize() reads it; decoding fails.
 */
function pngHeader(int $width, int $height): UploadedFile
{
    $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
    $png = "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));

    return UploadedFile::fake()->createWithContent('photo.png', $png);
}

it('rejects photos with too many pixels before decoding them', function () {
    $huge = validator(['photo' => pngHeader(9000, 9000)], ['photo' => [new MaxMegapixels(50)]]);
    $fine = validator(['photo' => pngHeader(4000, 3000)], ['photo' => [new MaxMegapixels(50)]]);

    expect($huge->fails())->toBeTrue()
        ->and($huge->errors()->first('photo'))->toContain('50 megapixels')
        ->and($fine->passes())->toBeTrue();
});

it('turns an unreadable recipe photo into a friendly error', function () {
    Storage::fake('public');
    $member = User::factory()->create();

    Livewire::actingAs($member)->test('pages::recipes.edit')
        ->set('form.title', 'Pie')
        ->set('photo', pngHeader(800, 600))
        ->call('save')
        ->assertHasErrors(['photo']);

    expect($member->recipes()->count())->toBe(0);
});

it('only accepts photos at the temporary upload endpoint', function (string $name, string $mime, int $status) {
    Storage::fake('local');

    $this->actingAs(User::factory()->create())
        ->postJson(URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5), absolute: false), [
            'files' => [UploadedFile::fake()->create($name, 10, $mime)],
        ])
        ->assertStatus($status);
})->with([
    'script disguised as svg' => ['evil.svg', 'image/svg+xml', 422],
    'php file' => ['shell.php', 'text/x-php', 422],
    'html page' => ['page.html', 'text/html', 422],
]);

it('never previews uploads that could carry script', function () {
    expect(config('livewire.temporary_file_upload.preview_mimes'))->toBe(['jpg', 'jpeg', 'png', 'gif', 'webp'])
        ->and(config('livewire.temporary_file_upload.rules'))->toContain('mimes:jpg,jpeg,png,webp,gif');
});

it('stores uploads only as re-encoded WebP files with random names', function () {
    Storage::fake('public');
    $member = User::factory()->create();

    Livewire::actingAs($member)->test('pages::recipes.edit')
        ->set('form.title', 'Pie')
        ->set('photo', UploadedFile::fake()->image('../../evil.php.jpg', 800, 600))
        ->call('save')
        ->assertHasNoErrors();

    $files = Storage::disk('public')->allFiles();

    expect($files)->toHaveCount(3);
    foreach ($files as $file) {
        expect($file)->toMatch('#^recipes/[a-z0-9]{26}-[a-z0-9]{6}-(400|800|1600)\.webp$#');
    }
});
