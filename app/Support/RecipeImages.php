<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Stores recipe photos as three 4:3 WebP sizes. Every upload is re-encoded, which strips EXIF
 * data such as GPS location. The recipe keeps a base path; each size adds "-{width}.webp".
 */
final class RecipeImages
{
    /** @var list<int> */
    public const WIDTHS = [400, 800, 1600];

    /**
     * Stores the photo and returns its base path.
     */
    public static function store(UploadedFile $file): string
    {
        $manager = ImageManager::usingDriver(Driver::class, decodeAnimation: false, strip: true);
        $base = 'recipes/'.Str::lower((string) Str::ulid()).'-'.Str::lower(Str::random(6));

        foreach (self::WIDTHS as $width) {
            $image = $manager->decodePath($file->getRealPath())
                ->coverDown($width, intdiv($width * 3, 4))
                ->encodeUsingFormat(Format::WEBP, quality: 80);

            Storage::disk('public')->put("{$base}-{$width}.webp", (string) $image);
        }

        return $base;
    }

    public static function delete(?string $base): void
    {
        // Seed photos are shared and ship with the app.
        if ($base !== null && ! SeedPhotos::isSeed($base)) {
            Storage::disk('public')->delete(array_map(fn (int $width) => "{$base}-{$width}.webp", self::WIDTHS));
        }
    }

    public static function url(?string $base, int $width = 800): ?string
    {
        return match (true) {
            $base === null => null,
            SeedPhotos::isSeed($base) => SeedPhotos::url($base, $width),
            default => Storage::disk('public')->url("{$base}-{$width}.webp"),
        };
    }

    public static function srcset(?string $base): ?string
    {
        if ($base === null) {
            return null;
        }

        return collect(self::WIDTHS)->map(fn (int $width) => self::url($base, $width)." {$width}w")->implode(', ');
    }
}
