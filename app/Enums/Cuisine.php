<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Cuisine: string
{
    use HasOptions;

    case African = 'African';
    case American = 'American';
    case Australian = 'Australian';
    case British = 'British';
    case Caribbean = 'Caribbean';
    case Chinese = 'Chinese';
    case French = 'French';
    case German = 'German';
    case Greek = 'Greek';
    case Indian = 'Indian';
    case Italian = 'Italian';
    case Japanese = 'Japanese';
    case Korean = 'Korean';
    case LatinAmerican = 'Latin American';
    case Mediterranean = 'Mediterranean';
    case Mexican = 'Mexican';
    case MiddleEastern = 'Middle Eastern';
    case Southern = 'Southern';
    case Thai = 'Thai';
    case Vietnamese = 'Vietnamese';
}
