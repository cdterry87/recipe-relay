@props(['recipe'])

{{-- The recipe's average, or "New" until it has a few ratings. --}}
@if ($recipe->hasEnoughRatings())
    <span {{ $attributes->class('rating-summary') }}>
        <x-ui.icon name="star" size="sm" class="rating-summary__star" />
        <span aria-hidden="true"><strong>{{ number_format($recipe->rating_avg, 1) }}</strong> · {{ number_format($recipe->rating_count) }} ratings</span>
        <span class="visually-hidden">Rated {{ number_format($recipe->rating_avg, 1) }} out of 5 from {{ number_format($recipe->rating_count) }} ratings</span>
    </span>
@else
    <span {{ $attributes->class(['rating-summary', 'rating-summary--new']) }}>
        <x-ui.icon name="sparkles" size="sm" />
        New<span class="visually-hidden">, not enough ratings yet</span>
    </span>
@endif
