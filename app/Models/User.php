<?php

namespace App\Models;

use App\Enums\Visibility;
use App\Models\Concerns\HasPublicUuid;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * `google_id`, `is_demo` and `is_sample` are never mass assignable: set them with forceFill().
 */
#[Fillable(['name', 'email', 'password', 'bio', 'timezone'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicUuid, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'UTC',
        'is_demo' => false,
        'is_sample' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_demo' => 'boolean',
            'is_sample' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Recipe, $this>
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * @return HasMany<Save, $this>
     */
    public function saves(): HasMany
    {
        return $this->hasMany(Save::class);
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Creators this member follows.
     *
     * @return BelongsToMany<User, $this, Follow, 'follow'>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id')
            ->using(Follow::class)
            ->as('follow')
            ->withTimestamps();
    }

    /**
     * Members who follow this creator.
     *
     * @return BelongsToMany<User, $this, Follow, 'follow'>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followee_id', 'follower_id')
            ->using(Follow::class)
            ->as('follow')
            ->withTimestamps();
    }

    /**
     * Members with at least one public recipe, with the figures creator cards show:
     * followers_count, public_recipes_count and public_saves (saves across public recipes).
     *
     * @param  Builder<User>  $query
     */
    public function scopeCreators(Builder $query): void
    {
        $query
            ->whereHas('recipes', fn (Builder $q) => $q->listed())
            ->withCount(['followers', 'recipes as public_recipes_count' => fn (Builder $q) => $q->where('visibility', Visibility::Public)])
            ->withSum(['recipes as public_saves' => fn (Builder $q) => $q->where('visibility', Visibility::Public)], 'saves_count');
    }

    /**
     * Busiest first: followers, then saves, then the newest account as a stable tie-breaker.
     *
     * @param  Builder<User>  $query
     */
    public function scopeTrending(Builder $query): void
    {
        $query->orderByDesc('followers_count')->orderByDesc('public_saves')->orderByDesc('users.id');
    }

    public function hasSaved(Recipe $recipe): bool
    {
        return $this->saves()->whereBelongsTo($recipe)->exists();
    }

    public function ratingFor(Recipe $recipe): ?int
    {
        $stars = $this->ratings()->whereBelongsTo($recipe)->value('stars');

        return $stars === null ? null : (int) $stars;
    }

    public function isFollowing(User $creator): bool
    {
        return $this->following()->whereKey($creator->getKey())->exists();
    }

    /**
     * Whether others can follow this member: anyone with at least one public recipe.
     */
    public function isFollowable(): bool
    {
        return $this->recipes()->listed()->exists();
    }

    /**
     * The most recipes this account may hold. The shared demo account gets fewer.
     */
    public function recipeLimit(): int
    {
        return $this->is_demo ? (int) config('relay.max_demo_recipes') : (int) config('relay.max_recipes');
    }

    /**
     * Whether the account has a password. Accounts made with Google sign-in start without one.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /**
     * Up to two initials for the avatar placeholder, such as "GH" for Grace Hopper.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
