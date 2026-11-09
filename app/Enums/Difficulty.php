<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Difficulty: string
{
    use HasOptions;

    case Easy = 'Easy';
    case Medium = 'Medium';
    case Hard = 'Hard';
}
