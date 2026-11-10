<?php

namespace App\Support;

use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Follow;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Builds the launch content and restores it every night: four fictional sample creators,
 * the 21 seed recipes from database/data/recipes.php, the demo account, and the saves,
 * ratings and follows between them. See sections B9 and B11.4 of docs/spec.md.
 *
 * Only demo and sample data is touched. Real accounts keep their recipes, and their saves
 * and ratings on seed recipes survive (unless the demo user deleted that recipe that day).
 */
final class DemoWorld
{
    /**
     * Fictional cooks. They have no password, so nobody can log in as them, and their
     * addresses use the reserved .invalid domain, so no email can reach anyone.
     *
     * @var array<string, array{name: string, bio: string, photo: string}>
     */
    public const CREATORS = [
        'rosa' => ['name' => 'Rosa Delgado', 'bio' => 'Weeknight dinners for a busy family of five. If it takes more than 30 minutes, it had better be worth it.', 'photo' => 'beef-tacos'],
        'sam' => ['name' => 'Sam Okafor', 'bio' => 'Big breakfasts, Sunday roasts and anything that feeds a crowd.', 'photo' => 'pancakes'],
        'mei' => ['name' => 'Mei Tanaka', 'bio' => 'Noodles, rice bowls and one-wok wonders. Always hungry for ramen.', 'photo' => 'pork-ramen'],
        'priya' => ['name' => 'Priya Shah', 'bio' => 'Curries on weeknights, cakes on weekends. Spice is a love language.', 'photo' => 'chocolate-cake'],
    ];

    /**
     * The demo account's starting point: who it follows and what it has saved (with stars,
     * or null for saved but not rated).
     */
    public const DEMO_FOLLOWS = ['rosa', 'mei'];

    /** @var array<string, int|null> */
    public const DEMO_SAVES = [
        'pork-ramen' => 5,
        'beef-tacos' => 4,
        'chocolate-cake' => 5,
        'grilled-salmon' => null,
    ];

    public const DEMO_BIO = 'Learning to cook one recipe at a time. Everything here is private and resets every night.';

    /** @var list<array<string, mixed>>|null */
    private static ?array $recipes = null;

    /**
     * @return list<array<string, mixed>>
     */
    public static function recipes(): array
    {
        return self::$recipes ??= require database_path('data/recipes.php');
    }

    public static function demoConfigured(): bool
    {
        return filled(config('relay.demo.email')) && filled(config('relay.demo.password'));
    }

    /**
     * Builds or restores everything in one transaction.
     *
     * @return array{demo: User|null, creators: int, recipes: int}
     *
     * @throws RuntimeException when a regular account already uses the demo or a sample address.
     */
    public static function reset(?Carbon $now = null): array
    {
        $now ??= now();
        $leftoverImages = [];

        $result = DB::transaction(function () use ($now, &$leftoverImages) {
            $creators = self::restoreCreators();
            $demo = self::demoConfigured() ? self::restoreDemoUser() : null;
            $people = array_values(array_filter([...array_values($creators), $demo]));
            $peopleIds = array_map(fn (User $user) => $user->id, $people);

            // Recipes whose counters change when the baseline is rebuilt.
            $touched = Save::query()->whereIn('user_id', $peopleIds)->pluck('recipe_id')
                ->merge(Rating::query()->whereIn('user_id', $peopleIds)->pluck('recipe_id'))
                ->all();

            if ($demo !== null) {
                $extra = $demo->recipes()->whereNull('seed_key')->get();
                $leftoverImages = $extra->pluck('image_path')->filter()->all();
                $extra->each->delete();
            }

            Save::query()->whereIn('user_id', $peopleIds)->delete();
            Rating::query()->whereIn('user_id', $peopleIds)->delete();
            Follow::query()->whereIn('follower_id', $peopleIds)->delete();
            DatabaseNotification::query()->where('notifiable_type', (new User)->getMorphClass())->whereIn('notifiable_id', $peopleIds)->delete();

            $owners = [...$creators, 'demo' => $demo];
            $recipes = self::restoreRecipes($owners, $now);

            self::restoreBaseline($creators, $demo, $recipes);

            Recipe::query()->whereIn('id', [...$touched, ...array_map(fn (Recipe $r) => $r->id, $recipes)])
                ->each(fn (Recipe $recipe) => $recipe->refreshCounters());

            if ($demo !== null) {
                // Visitors start fresh: no session or "remember me" login outlives the reset.
                DB::table('sessions')->where('user_id', $demo->id)->delete();
            }

            return ['demo' => $demo, 'creators' => count($creators), 'recipes' => count($recipes)];
        });

        foreach ($leftoverImages as $path) {
            RecipeImages::delete($path);
        }

        return $result;
    }

    /**
     * @return array<string, User>
     */
    private static function restoreCreators(): array
    {
        $creators = [];

        foreach (self::CREATORS as $key => $details) {
            $email = "{$key}@samples.invalid";
            $user = User::query()->where('email', $email)->first();

            if ($user !== null && ! $user->is_sample) {
                throw new RuntimeException("A regular account already uses {$email}, so it can't become a sample creator.");
            }

            $user ??= new User;
            $user->forceFill([
                'name' => $details['name'],
                'email' => $email,
                'password' => null,
                'bio' => $details['bio'],
                'timezone' => 'America/Chicago',
                'avatar_path' => SeedPhotos::path($details['photo']),
                'is_sample' => true,
                'is_demo' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'email_preferences' => null,
            ])->save();

            $creators[$key] = $user;
        }

        return $creators;
    }

    private static function restoreDemoUser(): User
    {
        $config = config('relay.demo');
        $user = User::query()->where('is_demo', true)->first();

        if ($user === null && User::query()->where('email', $config['email'])->exists()) {
            throw new RuntimeException("A regular account already uses {$config['email']}, so it can't become the demo account.");
        }

        $old = $user?->avatar_path;
        $user ??= new User;

        $user->forceFill([
            'name' => $config['name'],
            'email' => $config['email'],
            'password' => $config['password'],
            'bio' => self::DEMO_BIO,
            'timezone' => $config['timezone'],
            'avatar_path' => null,
            'google_id' => null,
            'digest_day' => 0,
            'digest_hour' => 9,
            'last_digest_at' => null,
            'email_preferences' => null,
            'is_demo' => true,
            'is_sample' => false,
            'email_verified_at' => $user->email_verified_at ?? now(),
            // Ends "remember me" logins from before the reset.
            'remember_token' => Str::random(60),
        ])->save();

        Avatars::delete($old);

        return $user;
    }

    /**
     * Restores every seed recipe to its original state, matched on seed_key, and recreates any
     * that were deleted. Rows keep their id, so real members' saves and ratings survive.
     *
     * @param  array<string, User|null>  $owners
     * @return array<string, Recipe>
     */
    private static function restoreRecipes(array $owners, Carbon $now): array
    {
        $restored = [];

        foreach (self::recipes() as $data) {
            $owner = $owners[$data['owner']] ?? null;

            if ($owner === null) {
                // No demo account configured: its recipes wait until there is one.
                Recipe::query()->where('seed_key', $data['seed_key'])->delete();

                continue;
            }

            $recipe = Recipe::query()->where('seed_key', $data['seed_key'])->first() ?? new Recipe;
            $oldImage = $recipe->image_path;
            $published = $now->copy()->subDays($data['days_ago'])->setTime(18, 0);
            $public = $data['visibility'] === Visibility::Public->value;

            $recipe->timestamps = false;
            $recipe->forceFill([
                'seed_key' => $data['seed_key'],
                'user_id' => $owner->id,
                'title' => $data['title'],
                'summary' => $data['summary'],
                'meal_type' => $data['meal_type'],
                'cuisine' => $data['cuisine'],
                'method' => $data['method'],
                'difficulty' => $data['difficulty'],
                'occasion' => $data['occasion'],
                'prep_minutes' => $data['prep_minutes'],
                'cook_minutes' => $data['cook_minutes'],
                'servings' => $data['servings'],
                'calories' => $data['calories'],
                'collections' => $data['collections'],
                'dietary_tags' => $data['dietary_tags'],
                'notes' => null,
                'video_url' => null,
                'visibility' => $data['visibility'],
                'status' => RecipeStatus::Complete,
                'image_path' => SeedPhotos::path($data['photo']),
                'published_at' => $public ? $published : null,
                // Already "announced", so a reset never notifies anyone.
                'announced_at' => $public ? $published : null,
                'created_at' => $published,
                'updated_at' => $published,
            ])->save();
            $recipe->timestamps = true;

            if ($oldImage !== null && $oldImage !== $recipe->image_path) {
                RecipeImages::delete($oldImage);
            }

            $recipe->ingredients()->delete();
            $recipe->steps()->delete();

            foreach ($data['ingredients'] as $position => [$quantity, $unit, $name]) {
                $recipe->ingredients()->create(['position' => $position + 1, 'quantity' => $quantity, 'unit' => $unit, 'name' => $name]);
            }

            foreach ($data['steps'] as $position => $body) {
                $recipe->steps()->create(['position' => $position + 1, 'body' => $body]);
            }

            $restored[$data['seed_key']] = $recipe;
        }

        return $restored;
    }

    /**
     * Sample creators follow each other and save and rate each other's public recipes, so
     * averages and counts look lived-in. Stars are fixed, so every reset looks the same.
     *
     * @param  array<string, User>  $creators
     * @param  array<string, Recipe>  $recipes
     */
    private static function restoreBaseline(array $creators, ?User $demo, array $recipes): void
    {
        foreach ($creators as $key => $creator) {
            foreach ($creators as $otherKey => $other) {
                if ($key !== $otherKey) {
                    Follow::query()->create(['follower_id' => $creator->id, 'followee_id' => $other->id]);
                }
            }
        }

        foreach ($recipes as $seedKey => $recipe) {
            if ($recipe->visibility !== Visibility::Public) {
                continue;
            }

            foreach ($creators as $key => $creator) {
                if ($creator->id === $recipe->user_id) {
                    continue;
                }

                self::saveAndRate($creator, $recipe, [5, 4, 5, 3, 5, 4][crc32($seedKey.$key) % 6]);
            }
        }

        if ($demo === null) {
            return;
        }

        foreach (self::DEMO_FOLLOWS as $key) {
            Follow::query()->create(['follower_id' => $demo->id, 'followee_id' => $creators[$key]->id]);
        }

        foreach (self::DEMO_SAVES as $seedKey => $stars) {
            if (isset($recipes[$seedKey])) {
                self::saveAndRate($demo, $recipes[$seedKey], $stars);
            }
        }
    }

    private static function saveAndRate(User $user, Recipe $recipe, ?int $stars): void
    {
        Save::query()->create(['user_id' => $user->id, 'recipe_id' => $recipe->id]);

        if ($stars !== null) {
            Rating::query()->create(['user_id' => $user->id, 'recipe_id' => $recipe->id, 'stars' => $stars]);
        }
    }
}
