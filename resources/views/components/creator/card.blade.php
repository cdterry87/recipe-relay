@props(['name', 'specialty' => null, 'saves' => null, 'recipes' => null, 'followers' => null, 'src' => null, 'href' => null])

<article {{ $attributes->class('creator-card') }}>
    <x-ui.avatar :name="$name" :src="$src" size="lg" />
    <h3 class="creator-card__name">
        @if ($href)<a href="{{ $href }}" wire:navigate>{{ $name }}</a>@else{{ $name }}@endif
    </h3>
    @if ($specialty)<p class="creator-card__specialty">{{ $specialty }}</p>@endif
    <p class="creator-card__stats">
        @if ($followers)<span>{{ $followers }} {{ str('follower')->plural((int) $followers) }}</span>@endif
        @if ($saves)<span class="creator-card__saves">{{ $saves }} saves</span>@endif
        @if ($recipes)<span>{{ $recipes }} {{ str('recipe')->plural((int) $recipes) }}</span>@endif
    </p>
</article>
