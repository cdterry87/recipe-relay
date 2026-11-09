<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
