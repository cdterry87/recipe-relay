@php
    $signUpOpen = App\Support\Registration::open();
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

    <section class="steps" aria-labelledby="steps-title">
        <header class="section__header">
            <p class="eyebrow">How it works</p>
            <h2 id="steps-title">Pass the <em>spoon</em></h2>
        </header>
        <ol class="steps__list">
            <li class="step">
                <h3>Save your recipes</h3>
                <p>Add ingredients and steps, then keep them private, share them by link, or make them public.</p>
            </li>
            <li class="step">
                <h3>Follow great cooks</h3>
                <p>Find recipes from people you like and get a weekly email with what they've posted.</p>
            </li>
            <li class="step">
                <h3>Relay the good ones</h3>
                <p>Rate what you've saved, and share your favorites by email or on social media.</p>
            </li>
        </ol>
    </section>
</x-layouts::app>
