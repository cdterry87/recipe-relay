<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Stores profile pictures. Every upload is re-encoded, which strips EXIF data such as GPS
 * location, and cropped from the centre to a square WebP image.
 */
final class Avatars
{
    public const SIZE = 400;

    public static function store(User $user, UploadedFile $file): void
    {
        $image = ImageManager::usingDriver(Driver::class, decodeAnimation: false, strip: true)
            ->decodePath($file->getRealPath())
            ->cover(self::SIZE, self::SIZE)
            ->encodeUsingFormat(Format::WEBP, quality: 85);

        $path = 'avatars/'.$user->uuid.'-'.Str::lower(Str::random(8)).'.webp';

        Storage::disk('public')->put($path, (string) $image);

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        self::delete($old);
    }

    /**
     * Uses one of the seed food photos as the avatar. This is how the demo account changes
     * its picture, since it can't upload files.
     */
    public static function useSeedPhoto(User $user, string $key): void
    {
        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => SeedPhotos::path($key)])->save();

        self::delete($old);
    }

    public static function remove(User $user): void
    {
        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => null])->save();

        self::delete($old);
    }

    public static function delete(?string $path): void
    {
        if ($path !== null && ! SeedPhotos::isSeed($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
