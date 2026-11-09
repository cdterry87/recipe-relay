@php
    $recipe = $this->recipe;
    $owner = $recipe->isOwnedBy(auth()->user());
    $rating = $this->myRating ?? 0;
@endphp

<div class="engage">
    <x-recipe.rating :recipe="$recipe" />

    @if ($recipe->saves_count > 0)
        <span class="engage__saves">{{ number_format($recipe->saves_count) }} {{ str('save')->plural($recipe->saves_count) }}</span>
    @endif

    @if (! $owner && $recipe->isSaveable())
        <x-ui.button
            :variant="$this->saved ? 'primary' : 'secondary'"
            size="sm"
            icon="heart"
            :class="$this->saved ? 'save-toggle is-saved' : 'save-toggle'"
            wire:click="toggleSave"
            wire:loading.attr="disabled"
            :aria-pressed="$this->saved ? 'true' : 'false'"
            :wire:confirm="$this->saved && $rating ? 'Removing this recipe from your cookbook also removes your '.$rating.'★ rating.' : null"
        >{{ $this->saved ? 'Saved' : 'Save' }}<span class="visually-hidden"> {{ $recipe->title }} to your cookbook</span></x-ui.button>

        @if ($this->saved)
            <div class="stars" role="group" aria-label="Your rating" x-data="{ hover: 0 }" x-on:mouseleave="hover = 0" x-bind:class="{ 'is-hovering': hover > 0 }">
                @for ($n = 1; $n <= 5; $n++)
                    <button type="button" @class(['stars__star', 'is-on' => $n <= $rating]) x-bind:class="{ 'is-hover': hover >= {{ $n }} }"
                        x-on:mouseenter="hover = {{ $n }}" wire:click="rate({{ $n }})" aria-pressed="{{ $rating === $n ? 'true' : 'false' }}">
                        <x-ui.icon name="star" />
                        <span class="visually-hidden">{{ $n }} {{ str('star')->plural($n) }}</span>
                    </button>
                @endfor
            </div>
        @else
            <span class="engage__hint">Save to rate</span>
        @endif
    @endif
</div>
