<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * The allowed sort orders. Anything else in the URL falls back to the page's default.
 */
enum RecipeSort: string
{
    use HasOptions;

    case Newest = 'newest';
    case Updated = 'updated';
    case TopRated = 'top-rated';
    case MostSaved = 'most-saved';
    case Quickest = 'quickest';
    case Title = 'a-z';
    // Only on the Cookbook, whose query selects a saved_at column.
    case Saved = 'recently-saved';

    public function label(): string
    {
        return match ($this) {
            self::Newest => 'Newest',
            self::Updated => 'Recently edited',
            self::TopRated => 'Top rated',
            self::MostSaved => 'Most saved',
            self::Quickest => 'Quickest',
            self::Title => 'A to Z',
            self::Saved => 'Recently saved',
        };
    }

    public function slug(): string
    {
        return $this->value;
    }
}
