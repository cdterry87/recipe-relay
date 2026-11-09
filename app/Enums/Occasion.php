<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Occasion: string
{
    use HasOptions;

    case Everyday = 'Everyday';
    case Birthday = 'Birthday';
    case Holiday = 'Holiday';
    case Christmas = 'Christmas';
    case Thanksgiving = 'Thanksgiving';
    case Easter = 'Easter';
    case Summer = 'Summer';
    case Winter = 'Winter';
    case GameDay = 'Game Day';
}
