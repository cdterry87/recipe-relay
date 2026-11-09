<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\View\View;

class HomeController
{
    /**
     * The home page uses real public recipes once there are enough to fill a row, and the
     * sample content from App\Support\Showcase until then.
     */
    public function __invoke(): View
    {
        $trending = Recipe::query()->listed()->with('user')
            ->orderByDesc('saves_count')->orderByDesc('rating_avg')->orderByDesc('published_at')
            ->limit(8)->get();

        $fresh = Recipe::query()->listed()->with('user')
            ->where('published_at', '>=', now()->subWeek())
            ->latest('published_at')
            ->limit(4)->get();

        return view('pages.home', [
            'trending' => $trending->count() >= 4 ? $trending : null,
            'fresh' => $fresh,
        ]);
    }
}
