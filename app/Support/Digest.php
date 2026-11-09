<?php

namespace App\Support;

use App\Models\Follow;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What goes into a member's weekly digest: new public recipes from creators they follow
 * (up to three each), then a few of the week's most popular new recipes. Private and
 * unlisted recipes never appear. Used by the email and by the /my/digest preview.
 */
final class Digest
{
    public const MAX_CREATORS = 10;

    public const RECIPES_PER_CREATOR = 3;

    public const TRENDING = 4;

    /**
     * @param  Collection<int, array{creator: User, recipes: EloquentCollection<int, Recipe>, more: int}>  $creators
     * @param  EloquentCollection<int, Recipe>  $trending
     */
    private function __construct(
        public readonly User $user,
        public readonly Carbon $since,
        public readonly Collection $creators,
        public readonly EloquentCollection $trending,
    ) {}

    /**
     * @param  Carbon|null  $since  Where the "new" window starts. By default, the last digest
     *                              or a week ago, whichever is later, so recipes never repeat.
     */
    public static function for(User $user, ?Carbon $now = null, ?Carbon $since = null): self
    {
        $now ??= now();
        $weekAgo = $now->copy()->subWeek();
        $since ??= $user->last_digest_at?->gt($weekAgo) ? $user->last_digest_at->copy() : $weekAgo;

        $fresh = Recipe::query()->listed()->with('user')
            ->whereIn('user_id', Follow::query()->where('follower_id', $user->id)->select('followee_id'))
            ->where('published_at', '>', $since)
            ->where('published_at', '<=', $now)
            ->latest('published_at')
            ->limit(200)
            ->get();

        $creators = collect();

        foreach ($fresh->groupBy('user_id')->take(self::MAX_CREATORS) as $recipes) {
            $first = $recipes->first();

            if ($first instanceof Recipe) {
                $creators->push([
                    'creator' => $first->user,
                    'recipes' => $recipes->take(self::RECIPES_PER_CREATOR)->values(),
                    'more' => max(0, $recipes->count() - self::RECIPES_PER_CREATOR),
                ]);
            }
        }

        $trending = Recipe::query()->listed()->with('user')
            ->where('published_at', '>', $weekAgo)
            ->where('published_at', '<=', $now)
            ->whereNot('user_id', $user->id)
            ->whereNotIn('id', $fresh->modelKeys())
            ->orderByDesc('saves_count')->orderByDesc('rating_avg')->orderByDesc('published_at')
            ->limit(self::TRENDING)
            ->get();

        return new self($user, $since, $creators, $trending);
    }

    /**
     * A digest is only sent when someone the member follows has shared something new.
     */
    public function isEmpty(): bool
    {
        return $this->creators->isEmpty();
    }

    public function recipeCount(): int
    {
        return $this->creators->sum(fn (array $entry) => $entry['recipes']->count() + $entry['more']);
    }

    public function subject(): string
    {
        $count = $this->recipeCount();

        return $count === 1
            ? 'Your weekly digest: 1 new recipe from cooks you follow'
            : "Your weekly digest: {$count} new recipes from cooks you follow";
    }
}
