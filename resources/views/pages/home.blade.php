<x-layouts::app :description="'Keep your favorite recipes and relay the good ones to friends, family and followers.'">
    <section class="hero" aria-labelledby="hero-title">
        <p class="hero__eyebrow">Cook it. Love it. Relay it.</p>
        <h1 class="hero__title" id="hero-title">Pass along the recipes you <em>love</em></h1>
        <p class="hero__lede">Keep your favorite recipes in one friendly place, then share the good ones with friends, family and followers.</p>
        <ul class="cluster hero__chips" aria-label="Coming soon">
            @foreach (['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Quick & Easy', 'Slow Cook'] as $index => $label)
                <li><x-ui.chip :tone="['primary', 'secondary', 'accent'][$index % 3]">{{ $label }}</x-ui.chip></li>
            @endforeach
        </ul>
        <x-ui.notice tone="info">Fresh recipes are on their way. {{ config('app.name') }} is being rebuilt from scratch.</x-ui.notice>
    </section>
</x-layouts::app>
