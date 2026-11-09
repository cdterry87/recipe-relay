<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RecipeStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Complete = 'complete';

    public function label(): string
    {
        return $this === self::Draft ? 'Draft' : 'Complete';
    }
}
