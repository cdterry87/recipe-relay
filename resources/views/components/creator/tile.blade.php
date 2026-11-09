@props(['creator'])

{{-- A creator card for a real User loaded with the creators() scope. --}}
<x-creator.card
    :name="$creator->name"
    :specialty="$creator->bio ? Illuminate\Support\Str::limit($creator->bio, 60) : null"
    :followers="$creator->followers_count ? number_format($creator->followers_count) : null"
    :saves="$creator->public_saves ? number_format((int) $creator->public_saves) : null"
    :recipes="$creator->public_recipes_count"
    :src="$creator->avatarUrl()"
    :href="route('creators.show', $creator)"
    {{ $attributes }}
/>
