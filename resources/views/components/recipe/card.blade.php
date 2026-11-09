@props(['title', 'image' => null, 'tag' => null, 'minutes' => null, 'calories' => null, 'saves' => null, 'rating' => null, 'by' => null, 'href' => null])

{{-- A recipe card: photo with a tag, title, a compact facts line, and the cook. --}}
<article {{ $attributes->class('recipe-card') }}>
    <div class="recipe-card__media">
        @if ($image)
            <img class="recipe-card__image" src="{{ $image }}" alt="" width="800" height="600" loading="lazy" decoding="async">
        @else
            <span class="recipe-card__placeholder" aria-hidden="true">🍽️</span>
        @endif
        @if ($tag)<span class="recipe-card__tag">{{ $tag }}</span>@endif
    </div>
    <div class="recipe-card__body">
        <h3 class="recipe-card__title">
            @if ($href)<a href="{{ $href }}">{{ $title }}</a>@else{{ $title }}@endif
        </h3>
        <p class="recipe-card__meta">
            @if ($minutes)<span>{{ App\Models\Recipe::formatMinutes($minutes) }}</span>@endif
            @if ($calories)<span>{{ $calories }} cal</span>@endif
            @if ($rating)<span class="recipe-card__rating"><x-ui.icon name="star" size="sm" /> {{ $rating }}<span class="visually-hidden"> out of 5</span></span>@endif
            @if ($saves)<span class="recipe-card__saves">{{ $saves }} saves</span>@endif
        </p>
        @if ($slot->isNotEmpty())<div class="recipe-card__badges">{{ $slot }}</div>@endif
        @if ($by)
            <p class="recipe-card__by">
                <x-ui.avatar :name="$by" size="xs" />
                <span>by {{ $by }}</span>
            </p>
        @endif
    </div>
</article>
