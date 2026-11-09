@php
    use App\Models\Recipe;
    $owner = $recipe->isOwnedBy(auth()->user());
    $hasImage = $recipe->image_path !== null;
    $sections = $recipe->ingredients->groupBy(fn ($row) => (string) $row->section);
    $facts = array_filter([
        'Prep' => Recipe::formatMinutes($recipe->prep_minutes),
        'Cook' => Recipe::formatMinutes($recipe->cook_minutes),
        'Total' => Recipe::formatMinutes($recipe->totalMinutes()),
        'Serves' => $recipe->servings,
        'Calories' => $recipe->calories,
        'Level' => $recipe->difficulty?->label(),
    ]);
@endphp

<x-layouts::app :title="$recipe->title" :description="$recipe->summary">
    <x-slot:head>
        @if ($isPublic)
            <link rel="canonical" href="{{ route('recipes.show', $recipe) }}">
            <meta property="og:type" content="article">
            <meta property="og:title" content="{{ $recipe->title }}">
            <meta property="og:url" content="{{ route('recipes.show', $recipe) }}">
            @if ($recipe->summary)<meta property="og:description" content="{{ $recipe->summary }}">@endif
            @if ($hasImage)
                <meta property="og:image" content="{{ $recipe->imageUrl(1600) }}">
                <meta name="twitter:card" content="summary_large_image">
            @endif
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @else
            {{-- Unlisted and private recipes stay out of search engines. --}}
            <meta name="robots" content="noindex, nofollow">
        @endif
    </x-slot:head>

    <article class="recipe" x-data="recipePage({{ $recipe->servings ?? 0 }})" x-bind:class="{ 'is-cooking': cooking }">
        @if ($owner)
            <div class="owner-bar">
                <p>
                    <x-ui.chip :tone="$recipe->isComplete() ? 'secondary' : 'subtle'">{{ $recipe->status->label() }}</x-ui.chip>
                    <x-ui.chip>{{ $recipe->visibility->label() }}</x-ui.chip>
                    <span class="muted">{{ $recipe->visibility->description() }}</span>
                </p>
                <x-ui.button size="sm" icon="pencil" :href="route('recipes.edit', $recipe)" wire:navigate>Edit recipe</x-ui.button>
            </div>
        @endif

        <header class="recipe__hero">
            <div class="recipe__intro">
                @if ($recipe->meal_type)<p class="eyebrow">{{ $recipe->meal_type->label() }}@if ($recipe->cuisine) · {{ $recipe->cuisine->label() }}@endif</p>@endif
                <h1 class="recipe__title">{{ $recipe->title }}</h1>
                @if ($recipe->summary)<p class="recipe__summary">{{ $recipe->summary }}</p>@endif
                <div class="recipe__by">
                    <a class="recipe__creator" href="{{ route('creators.show', $recipe->user) }}" wire:navigate>
                        <x-ui.avatar :user="$recipe->user" size="sm" />
                        <span>by <strong>{{ $recipe->user->name }}</strong></span>
                    </a>
                    @if ($isPublic)
                        <livewire:follow-button :creator="$recipe->user" />
                    @endif
                </div>
                <livewire:recipe-actions :recipe="$recipe" />
                @if ($recipe->collections?->isNotEmpty() || $recipe->dietary_tags?->isNotEmpty())
                    <ul class="cluster recipe__tags" aria-label="Collections and dietary tags">
                        @foreach ($recipe->collections ?? [] as $collection)
                            <li><a class="chip chip--subtle" href="{{ route('collections.show', $collection->slug()) }}">{{ $collection->label() }}</a></li>
                        @endforeach
                        @foreach ($recipe->dietary_tags ?? [] as $tag)
                            <li><a class="chip chip--secondary" href="{{ route('discover', ['diet' => [$tag->slug()]]) }}">{{ $tag->label() }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <figure class="recipe__photo">
                @if ($hasImage)
                    <img src="{{ $recipe->imageUrl(800) }}" srcset="{{ $recipe->imageSrcset() }}" sizes="(min-width: 60rem) 36rem, 100vw" alt="{{ $recipe->title }}" width="1600" height="1200">
                @else
                    <div class="recipe__photo-empty" aria-hidden="true">🍽️</div>
                @endif
            </figure>
        </header>

        @if ($facts)
            <dl class="facts-row">
                @foreach ($facts as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>
        @endif

        <div class="recipe__tools">
            <x-ui.button variant="secondary" size="sm" icon="fire" x-on:click="toggleCooking()" x-bind:aria-pressed="cooking">
                <span x-text="cooking ? 'Leave cook mode' : 'Cook mode'">Cook mode</span>
            </x-ui.button>
            <x-ui.button variant="ghost" size="sm" icon="printer" x-on:click="window.print()">Print</x-ui.button>
            <p class="recipe__wake muted" x-show="cooking" x-cloak role="status" x-text="awake ? 'Your screen will stay on while you cook.' : 'Bigger text is on. Your device may still dim the screen.'"></p>
        </div>

        <div class="recipe__body">
            <section class="recipe__ingredients" aria-labelledby="ingredients-title">
                <div class="recipe__section-top">
                    <h2 id="ingredients-title">Ingredients</h2>
                    @if ($recipe->servings)
                        <div class="scaler" role="group" aria-label="Servings">
                            <x-ui.button variant="secondary" size="sm" icon="minus" label="Fewer servings" x-on:click="servings = Math.max(1, servings - 1)" />
                            <span class="scaler__value" aria-live="polite"><span x-text="servings">{{ $recipe->servings }}</span> servings</span>
                            <x-ui.button variant="secondary" size="sm" icon="plus" label="More servings" x-on:click="servings = Math.min(99, servings + 1)" />
                        </div>
                    @endif
                </div>
                @foreach ($sections as $section => $rows)
                    @if ($section !== '')<h3 class="recipe__group">{{ $section }}</h3>@endif
                    <ul class="checklist">
                        @foreach ($rows as $row)
                            <li>
                                <label class="checklist__item">
                                    <input type="checkbox">
                                    <span>
                                        @if ($row->quantity)<strong class="checklist__amount" data-quantity="{{ $row->quantity }}" x-text="scale($el.dataset.quantity)">{{ $row->quantity }}</strong>@endif
                                        @if ($row->unit) {{ $row->unit }}@endif
                                        {{ $row->name }}@if ($row->note)<span class="muted">, {{ $row->note }}</span>@endif
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
                @if ($recipe->ingredients->isEmpty())<p class="muted">No ingredients yet.</p>@endif
            </section>

            <section class="recipe__steps" aria-labelledby="steps-title">
                <h2 id="steps-title">Steps</h2>
                <ol class="steps-list">
                    @foreach ($recipe->steps as $step)
                        <li class="steps-list__item" x-data="{ done: false }" x-bind:class="{ 'is-done': done }">
                            <button type="button" class="steps-list__toggle" x-on:click="done = ! done" x-bind:aria-pressed="done">
                                <span class="steps-list__number">{{ $loop->iteration }}</span>
                                <span class="visually-hidden" x-text="done ? 'Mark step {{ $loop->iteration }} as not done' : 'Mark step {{ $loop->iteration }} as done'">Mark step {{ $loop->iteration }} as done</span>
                            </button>
                            <p class="steps-list__body">{!! nl2br(e($step->body)) !!}</p>
                        </li>
                    @endforeach
                </ol>
                @if ($recipe->steps->isEmpty())<p class="muted">No steps yet.</p>@endif

                @if ($recipe->notes)
                    <aside class="recipe__notes" aria-labelledby="notes-title">
                        <h2 id="notes-title">Tips and <em>notes</em></h2>
                        <p>{!! nl2br(e($recipe->notes)) !!}</p>
                    </aside>
                @endif

                @if ($embed = $recipe->videoEmbedUrl())
                    <section class="recipe__video" aria-labelledby="video-title">
                        <h2 id="video-title">Watch it <em>made</em></h2>
                        <div class="video-frame">
                            <iframe src="{{ $embed }}" title="Video for {{ $recipe->title }}" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    </section>
                @endif
            </section>
        </div>
    </article>

    @if ($fromCreator->isNotEmpty())
        <section class="section" aria-labelledby="more-title">
            <h2 id="more-title">More from {{ $recipe->user->name }}</h2>
            <div class="card-grid">
                @foreach ($fromCreator as $card)<x-recipe.tile :recipe="$card" />@endforeach
            </div>
        </section>
    @endif

    @if ($alsoLike->isNotEmpty())
        <section class="section" aria-labelledby="like-title">
            <h2 id="like-title">You might also <em>like</em></h2>
            <div class="card-grid">
                @foreach ($alsoLike as $card)<x-recipe.tile :recipe="$card" />@endforeach
            </div>
        </section>
    @endif
</x-layouts::app>
