@props(['recipe'])

@php
    $shareable = $recipe->visibility->isShared() && $recipe->isComplete();
@endphp

{{-- The Share button and its sheet: copy link, email, social networks, the device's own share menu, and "Email a friend". --}}
<div class="share" x-data="shareSheet(@js(['url' => $recipe->shareUrl(), 'title' => $recipe->title, 'text' => $recipe->title.' on '.config('app.name')]))">
    <x-ui.button variant="secondary" size="sm" icon="share" x-on:click="open()" aria-haspopup="dialog">Share</x-ui.button>

    <dialog class="share-sheet" x-ref="dialog" aria-labelledby="share-title" x-on:click="if ($event.target === $el) close()">
        <div class="share-sheet__panel">
            <header class="share-sheet__top">
                <h2 id="share-title" class="share-sheet__title">Share <em>{{ $recipe->title }}</em></h2>
                <x-ui.button variant="ghost" size="sm" icon="x" label="Close" x-on:click="close()" />
            </header>

            @if ($shareable)
                @if ($recipe->visibility === App\Enums\Visibility::Unlisted)
                    <p class="notice notice--info">This recipe is unlisted, so you're sharing its private link. Anyone who has it can open the recipe.</p>
                @endif

                <div class="share-sheet__copy">
                    <label class="visually-hidden" for="share-url">Recipe link</label>
                    <input class="field__control" id="share-url" type="text" readonly value="{{ $recipe->shareUrl() }}" x-on:focus="$el.select()">
                    <x-ui.button size="sm" icon="link" x-on:click="copy()"><span x-text="copied ? 'Copied!' : 'Copy link'">Copy link</span></x-ui.button>
                </div>

                <ul class="share-sheet__networks">
                    @foreach (App\Support\ShareLinks::for($recipe) as $link)
                        <li>
                            <a class="share-sheet__network share-sheet__network--{{ $link['network'] }}" href="{{ $link['href'] }}" @if ($link['network'] !== 'email') target="_blank" rel="noopener noreferrer" @endif>
                                <span class="share-sheet__badge" aria-hidden="true">{{ mb_substr($link['label'], 0, 1) }}</span>
                                {{ $link['label'] }}@if ($link['network'] !== 'email')<span class="visually-hidden"> (opens in a new tab)</span>@endif
                            </a>
                        </li>
                    @endforeach
                    <li x-show="canShare" x-cloak>
                        <button type="button" class="share-sheet__network" x-on:click="native()">
                            <span class="share-sheet__badge" aria-hidden="true">⋯</span> More options
                        </button>
                    </li>
                </ul>

                <livewire:email-recipe :recipe="$recipe" />
            @else
                <p class="muted">
                    @if (! $recipe->isComplete())
                        This recipe is a draft. Add at least one ingredient and one step, then make it public or unlisted to share it.
                    @else
                        This recipe is private, so only you can see it. Make it public or unlisted to share it.
                    @endif
                </p>
                @can('update', $recipe)
                    <div><x-ui.button size="sm" icon="pencil" :href="route('recipes.edit', $recipe)" wire:navigate>Change visibility</x-ui.button></div>
                @endcan
            @endif
        </div>
    </dialog>
</div>
