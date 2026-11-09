<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Total time buckets for filtering. Recipes without a time never match.
 */
enum TimeFilter: string
{
    use HasOptions;

    case Fifteen = '15';
    case Thirty = '30';
    case Sixty = '60';
    case Longer = 'longer';

    public function label(): string
    {
        return match ($this) {
            self::Fifteen => '15 minutes or less',
            self::Thirty => '30 minutes or less',
            self::Sixty => 'An hour or less',
            self::Longer => 'More than an hour',
        };
    }

    public function slug(): string
    {
        return $this->value;
    }
}
