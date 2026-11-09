@php
    $signUpOpen = App\Support\Registration::open();
    $recipes = App\Support\Showcase::recipes();
    $meals = [
        ['Breakfast', '🥞', route('discover', ['meal' => 'breakfast'])],
        ['Lunch', '🥪', route('discover', ['meal' => 'lunch'])],
        ['Dinner', '🍝', route('discover', ['meal' => 'dinner'])],
        ['Dessert', '🍰', route('discover', ['meal' => 'dessert'])],
        ['Quick & Easy', '⏱️', route('collections.show', 'quick-and-easy')],
        ['Slow Cook', '🍲', route('collections.show', 'slow-cook')],
    ];
@endphp

<x-layouts::app :description="'Keep your favorite recipes and relay the good ones to friends, family and followers.'">
    <div class="home-band">
        <form class="home-search" action="{{ route('discover') }}" method="get" role="search">
            <label class="visually-hidden" for="home-search">Search recipes</label>
            <input class="home-search__input" id="home-search" type="search" name="q" maxlength="100" placeholder="Search recipes, ingredients or cooks, such as “tacos” or “pumpkin”">
            <x-ui.button type="submit" class="home-search__button" icon="search">Search</x-ui.button>
        </form>
        <p class="home-band__tagline">Home cooking, passed from <em>kitchen to kitchen</em></p>
    </div>

    <div class="home-hero">
        <section class="feature" aria-labelledby="feature-title">
            <span class="feature__emoji" aria-hidden="true">🥄</span>
            <div class="cluster">
                <x-ui.chip tone="brand">Free to use</x-ui.chip>
                <x-ui.chip>Private by default</x-ui.chip>
            </div>
            <h1 id="feature-title">Cook it. Love it. <em>Relay it.</em></h1>
            <p>Grandma's stew, the weeknight tacos, the cake everyone asks about. Save them here, guard the family secrets, and hand the rest to the people at your table.</p>
        </section>

        <aside class="promo" aria-labelledby="promo-title">
            <p class="eyebrow">{{ config('app.name') }}</p>
            <h2 id="promo-title">A home for your <em>recipe box</em></h2>
            <p>Write down the dishes your family asks for, keep an eye on cooks whose food you like, and hear from them once a week.</p>
            <div class="promo__actions">
                @auth
                    <x-ui.button variant="inverse" :href="route('settings.profile')">Set up your profile</x-ui.button>
                @else
                    @if ($signUpOpen)
                        <x-ui.button variant="inverse" :href="route('register')">Create a free account</x-ui.button>
                        <x-ui.button variant="outline-inverse" :href="route('login')">Log in</x-ui.button>
                    @else
                        <x-ui.button variant="inverse" :href="route('login')">Try the demo</x-ui.button>
                    @endif
                @endauth
            </div>
            <p class="promo__note">No cost and no ads. Your recipes stay private until you choose to share them.</p>
        </aside>
    </div>

    <section class="section" aria-labelledby="recipes-title">
        <div class="section__top">
            <header class="section__header">
                <p class="eyebrow">Fresh from the community</p>
                <h2 id="recipes-title">Trending <em>recipes</em></h2>
            </header>
            <a class="more-link" href="{{ route('discover', ['sort' => 'most-saved']) }}">View more <span aria-hidden="true">→</span><span class="visually-hidden"> trending recipes</span></a>
        </div>
        <div class="card-grid">
            @if ($trending)
                @foreach ($trending as $recipe)
                    <x-recipe.tile :recipe="$recipe" />
                @endforeach
            @else
                @foreach ($recipes as $recipe)
                    <x-recipe.card
                        :title="$recipe['title']"
                        :image="asset('images/samples/'.$recipe['image'].'.webp')"
                        :tag="$recipe['tag']"
                        :minutes="$recipe['minutes']"
                        :calories="$recipe['calories']"
                        :saves="$recipe['saves']"
                        :by="$recipe['by']"
                    />
                @endforeach
            @endif
        </div>
    </section>

    @if ($fresh->isNotEmpty())
        <section class="section" aria-labelledby="fresh-title">
            <div class="section__top">
                <header class="section__header">
                    <p class="eyebrow">Just out of the oven</p>
                    <h2 id="fresh-title">New this <em>week</em></h2>
                </header>
                <a class="more-link" href="{{ route('discover') }}">View more <span aria-hidden="true">→</span><span class="visually-hidden"> new recipes</span></a>
            </div>
            <div class="card-grid">
                @foreach ($fresh as $recipe)
                    <x-recipe.tile :recipe="$recipe" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="section" aria-labelledby="meals-title">
        <header class="section__header">
            <p class="eyebrow">Browse by craving</p>
            <h2 id="meals-title">Find your next <em>meal</em></h2>
        </header>
        <ul class="tiles">
            @foreach ($meals as [$name, $emoji, $href])
                <li>
                    <a class="tile" href="{{ $href }}">
                        <span class="tile__art" aria-hidden="true">{{ $emoji }}</span>
                        <span class="tile__name">{{ $name }}</span>
                        <span class="tile__note">Browse recipes →</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="facts" aria-label="Why {{ config('app.name') }}">
        <div>
            <p class="facts__value">Private</p>
            <p class="facts__label">Until you say otherwise</p>
        </div>
        <div>
            <p class="facts__value">Weekly</p>
            <p class="facts__label">Digest from cooks you follow</p>
        </div>
        <div>
            <p class="facts__value">Zero</p>
            <p class="facts__label">Ads, ever</p>
        </div>
    </section>

    <section class="creators-panel" aria-labelledby="creators-title">
        <div class="section__top">
            <header class="section__header">
                <p class="eyebrow">Cooks worth following</p>
                <h2 id="creators-title">Trending <em>creators</em></h2>
            </header>
            <a class="more-link" href="{{ route('creators') }}">View more <span aria-hidden="true">→</span><span class="visually-hidden"> trending creators</span></a>
        </div>
        <div class="creators-panel__list">
            @foreach (App\Support\Showcase::creators() as $creator)
                <x-creator.card :name="$creator['name']" :specialty="$creator['specialty']" :saves="$creator['saves']" :recipes="$creator['recipes']" />
            @endforeach
        </div>
    </section>

    <section class="features" aria-labelledby="features-title">
        <div class="features__copy">
            <h2 id="features-title">From your kitchen to <em>theirs</em></h2>
            <ul class="features__list">
                <li class="features__item">
                    <h3>Your recipes, your rules</h3>
                    <p>Jot down what goes in and how it comes together, then decide who sees it: just you, anyone holding the link, or the whole community.</p>
                </li>
                <li class="features__item">
                    <h3>Find and save favorites</h3>
                    <p>Narrow things down by meal, collection, cooking time or stars, tuck the good ones into your cookbook, and give a rating once you've made them.</p>
                </li>
                <li class="features__item">
                    <h3>Follow and pass it on</h3>
                    <p>Keep up with your favorite cooks through a Sunday email round-up, and send a recipe to a friend or post it wherever you like.</p>
                </li>
            </ul>
            <div class="features__actions">
                @auth
                    <x-ui.button variant="inverse" :href="route('settings.profile')">Set up your profile</x-ui.button>
                @else
                    @if ($signUpOpen)
                        <x-ui.button variant="inverse" :href="route('register')">Create a free account</x-ui.button>
                    @else
                        <x-ui.button variant="inverse" :href="route('login')">Try the demo</x-ui.button>
                    @endif
                    <x-ui.button variant="outline-inverse" :href="route('discover')">Browse recipes</x-ui.button>
                @endauth
            </div>
            <p class="features__note">Made for home cooks of every age.</p>
        </div>
        <div class="features__art" aria-hidden="true">
            <span class="features__badge">Saved to your cookbook</span>
            <x-recipe.card :title="$recipes[1]['title']" :image="asset('images/samples/'.$recipes[1]['image'].'.webp')" :tag="$recipes[1]['tag']"
                :minutes="$recipes[1]['minutes']" :calories="$recipes[1]['calories']" :saves="$recipes[1]['saves']" :by="$recipes[1]['by']" />
        </div>
    </section>
</x-layouts::app>
