@props(['name', 'specialty' => null, 'saves' => null, 'recipes' => null, 'src' => null])

<article {{ $attributes->class('creator-card') }}>
    <x-ui.avatar :name="$name" :src="$src" size="lg" />
    <h3 class="creator-card__name">{{ $name }}</h3>
    @if ($specialty)<p class="creator-card__specialty">{{ $specialty }}</p>@endif
    <p class="creator-card__stats">
        @if ($saves)<span class="creator-card__saves">{{ $saves }} saves</span>@endif
        @if ($recipes)<span>{{ $recipes }} recipes</span>@endif
    </p>
</article>
