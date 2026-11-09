@props(['recipe', 'href' => null])

{{-- A recipe card for a real Recipe model. Unlisted recipes link to their share address. --}}
<x-recipe.card
    :title="$recipe->title"
    :image="$recipe->imageUrl(800)"
    :tag="$recipe->meal_type?->label()"
    :minutes="$recipe->totalMinutes()"
    :calories="$recipe->calories"
    :saves="$recipe->saves_count > 0 ? number_format($recipe->saves_count) : null"
    :rating="$recipe->hasEnoughRatings() ? number_format($recipe->rating_avg, 1) : null"
    :by="$recipe->user->name"
    :href="$href ?? $recipe->shareUrl()"
    {{ $attributes }}
>{{ $slot }}</x-recipe.card>
