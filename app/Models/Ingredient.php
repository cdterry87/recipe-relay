<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['position', 'section', 'quantity', 'unit', 'name', 'note'])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory, HasPublicUuid;
}
