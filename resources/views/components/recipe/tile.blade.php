@props(['recipe', 'href' => null])

{{-- A recipe card for a real Recipe model. --}}
<x-recipe.card
    :title="$recipe->title"
    :image="$recipe->imageUrl(800)"
    :tag="$recipe->meal_type?->label()"
    :minutes="$recipe->totalMinutes()"
    :calories="$recipe->calories"
    :saves="$recipe->saves_count > 0 ? number_format($recipe->saves_count) : null"
    :by="$recipe->user->name"
    :href="$href ?? route('recipes.show', $recipe)"
    {{ $attributes }}
>{{ $slot }}</x-recipe.card>
