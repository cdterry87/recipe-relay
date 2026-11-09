<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Visibility: string
{
    use HasOptions;

    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Unlisted => 'Unlisted',
            self::Public => 'Public',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => 'Only you can see it.',
            self::Unlisted => 'Anyone with the link can see it. It is not listed anywhere.',
            self::Public => 'Everyone can find and see it.',
        };
    }

    /**
     * Whether other people can reach the recipe at all.
     */
    public function isShared(): bool
    {
        return $this !== self::Private;
    }
}
