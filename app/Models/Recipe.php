<?php

namespace App\Models;

use App\Enums\Collection;
use App\Enums\Cuisine;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\Method;
use App\Enums\Occasion;
use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Concerns\HasPublicUuid;
use App\Support\RecipeImages;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $title
 * @property string $share_token
 * @property string|null $summary
 * @property Visibility $visibility
 * @property RecipeStatus $status
 * @property string|null $image_path
 * @property int|null $prep_minutes
 * @property int|null $cook_minutes
 * @property int $total_minutes
 * @property int|null $servings
 * @property Difficulty|null $difficulty
 * @property MealType|null $meal_type
 * @property Cuisine|null $cuisine
 * @property Method|null $method
 * @property Occasion|null $occasion
 * @property \Illuminate\Support\Collection<int, Collection>|null $collections
 * @property \Illuminate\Support\Collection<int, DietaryTag>|null $dietary_tags
 * @property int|null $calories
 * @property string|null $notes
 * @property string|null $video_url
 * @property float $rating_avg
 * @property int $rating_count
 * @property int $saves_count
 * @property Carbon|null $published_at
 * @property string|null $seed_key
 *
 * A recipe. Ownership, visibility, status, the image, counters and the share token are never
 * mass assignable; actions set them with forceFill().
 */
#[Fillable([
    'title', 'summary', 'prep_minutes', 'cook_minutes', 'servings', 'difficulty', 'meal_type',
    'cuisine', 'method', 'occasion', 'collections', 'dietary_tags', 'calories', 'notes', 'video_url',
])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory, HasPublicUuid;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'private',
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (Recipe $recipe) {
            $recipe->share_token ??= self::newShareToken();
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'visibility' => Visibility::class,
            'status' => RecipeStatus::class,
            'difficulty' => Difficulty::class,
            'meal_type' => MealType::class,
            'cuisine' => Cuisine::class,
            'method' => Method::class,
            'occasion' => Occasion::class,
            'collections' => AsEnumCollection::of(Collection::class),
            'dietary_tags' => AsEnumCollection::of(DietaryTag::class),
            'prep_minutes' => 'integer',
            'cook_minutes' => 'integer',
            'total_minutes' => 'integer',
            'servings' => 'integer',
            'calories' => 'integer',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
            'saves_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public static function newShareToken(): string
    {
        return Str::random(32);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Ingredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('position');
    }

    /**
     * @return HasMany<Step, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('position');
    }

    /**
     * Recipes anyone may find: public only. Unlisted and private recipes are never listed.
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('visibility', Visibility::Public);
    }

    /**
     * @param  Builder<Recipe>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->whereBelongsTo($user);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    public function isComplete(): bool
    {
        return $this->status === RecipeStatus::Complete;
    }

    /**
     * The address to share: the secret link for unlisted recipes, the normal page otherwise.
     */
    public function shareUrl(): string
    {
        return $this->visibility === Visibility::Unlisted
            ? route('recipes.shared', $this->share_token)
            : route('recipes.show', $this);
    }

    /**
     * Prep plus cook time, worked out here so it is right before the row is reloaded.
     */
    public function totalMinutes(): ?int
    {
        $total = ($this->prep_minutes ?? 0) + ($this->cook_minutes ?? 0);

        return $total > 0 ? $total : null;
    }

    /**
     * Formats a duration the way pages show it, such as "20 min" or "1h 15m".
     */
    public static function formatMinutes(?int $minutes): ?string
    {
        if (! $minutes) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? "{$hours}h" : "{$hours}h {$rest}m";
    }

    public function imageUrl(int $width = 800): ?string
    {
        return RecipeImages::url($this->image_path, $width);
    }

    public function imageSrcset(): ?string
    {
        return RecipeImages::srcset($this->image_path);
    }

    /**
     * An embed address built by us from an allow-listed YouTube or Vimeo link, or null.
     */
    public function videoEmbedUrl(): ?string
    {
        $url = (string) $this->video_url;

        if (preg_match('~^https://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1];
        }

        if (preg_match('~^https://(?:www\.)?vimeo\.com/(\d{6,12})(?:[/?#]|$)~', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1];
        }

        return null;
    }
}
