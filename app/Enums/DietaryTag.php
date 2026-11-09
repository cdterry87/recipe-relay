<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DietaryTag: string
{
    use HasOptions;

    case Vegan = 'Vegan';
    case Vegetarian = 'Vegetarian';
    case GlutenFree = 'Gluten-Free';
    case DairyFree = 'Dairy-Free';
    case NutFree = 'Nut-Free';
    case Keto = 'Keto';
    case LowCarb = 'Low-Carb';
    case HighProtein = 'High-Protein';
    case Halal = 'Halal';
    case Kosher = 'Kosher';
}
