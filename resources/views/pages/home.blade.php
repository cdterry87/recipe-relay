@php
    $signUpOpen = App\Support\Registration::open();
    $recipes = App\Support\Showcase::recipes();
    $meals = [
        ['Breakfast', '🥞'], ['Lunch', '🥪'], ['Dinner', '🍝'],
        ['Dessert', '🍰'], ['Quick & Easy', '⏱️'], ['Slow Cook', '🍲'],
    ];
@endphp

<x-layouts::app :description="'Keep your favorite recipes and relay the good ones to friends, family and followers.'">
    <div class="home-band">
        <p class="home-band__tagline">Easy recipes, shared with <em>the people you love</em></p>
    </div>

    <div class="home-hero">
        <section class="feature" aria-labelledby="feature-title">
            <span class="feature__emoji" aria-hidden="true">🥄</span>
            <div class="cluster">
                <x-ui.chip tone="primary">Coming soon</x-ui.chip>
                <x-ui.chip>Fresh recipes</x-ui.chip>
            </div>
            <h1 id="feature-title">Cook it. Love it. <em>Relay it.</em></h1>
            <p>Keep every recipe you love in one place, keep the secret ones private, and pass the best ones along to friends, family and followers.</p>
        </section>

        <aside class="promo" aria-labelledby="promo-title">
            <p class="eyebrow">{{ config('app.name') }}</p>
            <h2 id="promo-title">Your recipes, <em>finally</em> shared</h2>
            <p>Save your own recipes, follow cooks you love, and get a weekly digest of what they've been making.</p>
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
            <p class="promo__note">Free · Private by default · No ads</p>
        </aside>
    </div>

    <section class="section" aria-labelledby="recipes-title">
        <div class="section__top">
            <header class="section__header">
                <p class="eyebrow">What everyone's cooking</p>
                <h2 id="recipes-title">Trending <em>recipes</em></h2>
            </header>
            <a class="more-link" href="{{ route('discover') }}">View more <span aria-hidden="true">→</span><span class="visually-hidden"> trending recipes</span></a>
        </div>
        <div class="card-grid">
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
        </div>
    </section>

    <section class="section" aria-labelledby="meals-title">
        <header class="section__header">
            <p class="eyebrow">Something for every appetite</p>
            <h2 id="meals-title">Recipes for every <em>meal</em></h2>
        </header>
        <ul class="tiles">
            @foreach ($meals as [$name, $emoji])
                <li class="tile">
                    <span class="tile__art" aria-hidden="true">{{ $emoji }}</span>
                    <span class="tile__name">{{ $name }}</span>
                    <span class="tile__note">Coming soon</span>
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
                <p class="eyebrow">Real people sharing real recipes</p>
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
            <h2 id="features-title">Every recipe you love, in one <em>place</em></h2>
            <ul class="features__list">
                <li class="features__item">
                    <h3>Your recipes, your rules</h3>
                    <p>Write down ingredients and steps, then keep each recipe private, share it with an unlisted link, or make it public for everyone.</p>
                </li>
                <li class="features__item">
                    <h3>Find and save favorites</h3>
                    <p>Search by meal, collection, time and star rating, save what looks good to your cookbook, and rate what you've tried.</p>
                </li>
                <li class="features__item">
                    <h3>Follow and relay</h3>
                    <p>Follow cooks you love, get a weekly digest of what they've posted, and share recipes by email or on social media.</p>
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
            <p class="features__note">Free · Private by default · No ads</p>
        </div>
        <div class="features__art" aria-hidden="true">
            <span class="features__badge">Saved to your cookbook</span>
            <x-recipe.card :title="$recipes[1]['title']" :image="asset('images/samples/'.$recipes[1]['image'].'.webp')" :tag="$recipes[1]['tag']"
                :minutes="$recipes[1]['minutes']" :calories="$recipes[1]['calories']" :saves="$recipes[1]['saves']" :by="$recipes[1]['by']" />
        </div>
    </section>
</x-layouts::app>
