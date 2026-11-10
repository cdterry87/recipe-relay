<?php

namespace App\Support;

/**
 * The launch photos in public/images/seed, one per seed recipe, each at the RecipeImages
 * widths. Recipes and avatars point at them with a "seed/{key}" path, so many rows can share
 * one photo without copies. They ship with the app and are never deleted.
 */
final class SeedPhotos
{
    public const PREFIX = 'seed/';

    public static function isSeed(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::PREFIX);
    }

    public static function path(string $key): string
    {
        return self::PREFIX.$key;
    }

    public static function url(string $path, int $width): string
    {
        return asset('images/'.$path.'-'.$width.'.webp');
    }

    /**
     * Every photo, as key => label, for the demo account's picker.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return collect(DemoWorld::recipes())
            ->mapWithKeys(fn (array $recipe) => [$recipe['photo'] => $recipe['title']])
            ->sort()
            ->all();
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }
}
