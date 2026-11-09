<?php

namespace App\Http\Controllers;

use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Follow;
use App\Models\Recipe;
use App\Models\Save;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController
{
    /**
     * The home page uses real recipes and creators once there are enough to fill a row, and
     * the sample content from App\Support\Showcase until then. Members also see recipes
     * from people they follow and what they saved last.
     */
    public function __invoke(): View
    {
        $user = auth()->user();

        $trending = Recipe::query()->listed()->with('user')
            ->orderByDesc('saves_count')->orderByDesc('rating_avg')->orderByDesc('published_at')
            ->limit(8)->get();

        $fresh = Recipe::query()->listed()->with('user')
            ->where('published_at', '>=', now()->subWeek())
            ->latest('published_at')
            ->limit(4)->get();

        $creators = User::query()->creators()->trending()->limit(4)->get();

        return view('pages.home', [
            'trending' => $trending->count() >= 4 ? $trending : null,
            'fresh' => $fresh,
            'creators' => $creators->count() >= 4 ? $creators : null,
            'featured' => $this->featuredCreator(),
            'followed' => $user ? Recipe::query()->listed()->with('user')
                ->whereIn('user_id', Follow::query()->where('follower_id', $user->id)->select('followee_id'))
                ->latest('published_at')->limit(4)->get() : collect(),
            'saved' => $user ? Recipe::query()->with('user')
                ->whereIn('visibility', [Visibility::Public, Visibility::Unlisted])->where('status', RecipeStatus::Complete)
                ->joinSub(Save::query()->whereBelongsTo($user)->select('recipe_id', 'created_at as saved_at'), 'my_saves', 'my_saves.recipe_id', '=', 'recipes.id')
                ->orderByDesc('my_saves.saved_at')->select('recipes.*')->limit(4)->get() : collect(),
        ]);
    }

    /**
     * The creator with the most followers among those who shared a public recipe in the
     * past 30 days, picked once a day.
     */
    private function featuredCreator(): ?User
    {
        $uuid = Cache::remember('home:featured-creator:'.today()->toDateString(), now()->addDay(), function () {
            return User::query()
                ->whereHas('recipes', fn (Builder $q) => $q->listed()->where('published_at', '>=', now()->subDays(30)))
                ->withCount('followers')
                ->orderByDesc('followers_count')->orderByDesc('id')
                ->value('uuid') ?? '';
        });

        if ($uuid === '') {
            return null;
        }

        $creator = User::query()->creators()->where('uuid', $uuid)->first();

        if ($creator === null) {
            return null;
        }

        $creator->setRelation('latestRecipes', $creator->recipes()->listed()->with('user')->latest('published_at')->limit(3)->get());

        return $creator;
    }
}
