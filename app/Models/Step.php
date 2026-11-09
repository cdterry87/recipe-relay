<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\StepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['position', 'body'])]
class Step extends Model
{
    /** @use HasFactory<StepFactory> */
    use HasFactory, HasPublicUuid;
}
