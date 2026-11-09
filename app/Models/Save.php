<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\SaveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recipe in a member's cookbook. Created and removed only through App\Actions\Cookbook,
 * which keeps the recipe's counters and the member's rating in step.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $recipe_id
 */
#[Fillable(['user_id', 'recipe_id'])]
class Save extends Model
{
    /** @use HasFactory<SaveFactory> */
    use HasFactory, HasPublicUuid;

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
