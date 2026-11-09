<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's 1–5 star rating. A member may only rate a recipe they have saved, and unsaving
 * removes the rating (App\Actions\Cookbook).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $recipe_id
 * @property int $stars
 */
#[Fillable(['user_id', 'recipe_id', 'stars'])]
class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory, HasPublicUuid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['stars' => 'integer'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
