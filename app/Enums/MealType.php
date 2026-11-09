<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MealType: string
{
    use HasOptions;

    case Breakfast = 'Breakfast';
    case Brunch = 'Brunch';
    case Lunch = 'Lunch';
    case Dinner = 'Dinner';
    case Snack = 'Snack';
    case Appetizer = 'Appetizer';
    case Side = 'Side';
    case Soup = 'Soup';
    case Salad = 'Salad';
    case Dessert = 'Dessert';
    case Drink = 'Drink';
}
