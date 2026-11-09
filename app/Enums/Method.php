<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Method: string
{
    use HasOptions;

    case AirFryer = 'Air Fryer';
    case Assembled = 'Assembled';
    case Baked = 'Baked';
    case Grilled = 'Grilled';
    case InstantPot = 'Instant Pot';
    case Fried = 'Fried';
    case NoCook = 'No Cook';
    case Roasted = 'Roasted';
    case SlowCooker = 'Slow Cooker';
    case Smoked = 'Smoked';
    case Steamed = 'Steamed';
    case StirFried = 'Stir-Fried';
    case Stovetop = 'Stovetop';
}
