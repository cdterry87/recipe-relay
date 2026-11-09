<?php

use App\Actions\SaveRecipe;
use App\Enums\Collection;
use App\Enums\Cuisine;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\Method;
use App\Enums\Occasion;
use App\Enums\Visibility;
use App\Livewire\Forms\RecipeForm;
use App\Models\Recipe;
use App\Support\RecipeImages;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The one-page recipe editor, for new recipes and existing ones.
 */
new #[Layout('layouts::app', ['width' => 'reading'])] class extends Component
{
    use WithFileUploads;

    /**
     * The recipe being edited, by UUID. The component never holds the model itself, so its
     * database id stays off the page.
     */
    #[Locked]
    public ?string $recipeUuid = null;

    public RecipeForm $form;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    public bool $removePhoto = false;

    public function mount(?Recipe $recipe = null): void
    {
        if ($recipe?->exists) {
            Gate::authorize('update', $recipe);
            $this->recipeUuid = $recipe->uuid;
        } else {
            Gate::authorize('create', Recipe::class);
        }

        $this->form->fillFrom($this->recipe);
    }

    #[Computed]
    public function recipe(): ?Recipe
    {
        return $this->recipeUuid === null
            ? null
            : Recipe::query()->with('ingredients', 'steps')->where('uuid', $this->recipeUuid)->firstOrFail();
    }

    public function rendering($view): void
    {
        $view->with('recipe', $this->recipe);
        $view->title($this->recipe ? 'Edit '.$this->recipe->title : 'New recipe');
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['form.prep_minutes', 'form.cook_minutes', 'form.difficulty', 'form.method'], true)) {
            if ($ticked = $this->form->applySuggestions()) {
                $this->dispatch('toast', type: 'info', message: 'We ticked '.implode(' and ', $ticked).' for you. Untick it if it doesn\'t fit.');
            }
        }

        if (str_starts_with($name, 'form.collections')) {
            $this->form->noteUnticked();
        }
    }

    public function addIngredient(): void
    {
        $this->form->addIngredient();
    }

    public function addStep(): void
    {
        $this->form->addStep();
    }

    public function moveIngredient(string $key, int $position): void
    {
        $this->form->move('ingredients', $key, $position);
    }

    public function moveStep(string $key, int $position): void
    {
        $this->form->move('steps', $key, $position);
    }

    public function removeIngredient(string $key): void
    {
        $this->form->remove('ingredients', $key);
    }

    public function removeStep(string $key): void
    {
        $this->form->remove('steps', $key);
    }

    public function save(SaveRecipe $saveRecipe): void
    {
        $this->recipe
            ? Gate::authorize('update', $this->recipe)
            : Gate::authorize('create', Recipe::class);

        $this->form->dropBlankRows();
        $ingredients = $this->form->ingredientRows();
        $steps = $this->form->stepRows();

        try {
            $this->form->validate();
            $this->validate(['photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=400,min_height=300,max_width=10000,max_height=10000']], [
                'photo.dimensions' => 'Choose a photo at least 400 pixels wide and 300 tall.',
            ], ['photo' => 'photo']);
        } finally {
            // Always leave a blank row to type into.
            if ($this->form->ingredients === []) {
                $this->form->addIngredient();
            }

            if ($this->form->steps === []) {
                $this->form->addStep();
            }
        }

        $visibility = Visibility::from($this->form->visibility);

        if ($visibility->isShared()) {
            if (! auth()->user()->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['form.visibility' => 'Confirm your email address before sharing recipes.']);
            }

            if ($ingredients === [] || $steps === []) {
                throw ValidationException::withMessages(['form.visibility' => 'Add at least one ingredient and one step before sharing this recipe.']);
            }
        }

        $isNew = $this->recipe === null;

        $saved = $saveRecipe->handle(
            auth()->user(),
            $this->recipe,
            $this->form->details(),
            $ingredients,
            $steps,
            $visibility,
            $this->photo,
            $this->removePhoto,
        );

        $this->recipeUuid = $saved->uuid;
        unset($this->recipe);

        $this->photo?->delete();
        $this->reset('photo', 'removePhoto');

        if ($isNew) {
            session()->flash('toast', ['type' => 'success', 'message' => 'Your recipe is saved. Keep going, or view it any time.']);
            $this->redirectRoute('recipes.edit', $saved, navigate: true);

            return;
        }

        $this->form->fillFrom($this->recipe);
        $this->dispatch('toast', type: 'success', message: $saved->isComplete() ? 'Recipe saved.' : 'Draft saved. Add an ingredient and a step to finish it.');
    }

    public function resetShareLink(): void
    {
        Gate::authorize('update', $this->recipe);

        $this->recipe->forceFill(['share_token' => Recipe::newShareToken()])->save();
        unset($this->recipe);

        $this->dispatch('toast', type: 'success', message: 'New link made. The old one no longer works.');
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->recipe);

        RecipeImages::delete($this->recipe->image_path);
        $this->recipe->delete();

        session()->flash('toast', ['type' => 'success', 'message' => 'Recipe deleted.']);
        $this->redirectRoute('my.recipes', navigate: true);
    }
};
?>

<div class="editor">
    <header class="stack stack--sm">
        <p class="eyebrow">{{ $recipe ? 'Edit recipe' : 'New recipe' }}</p>
        <h1>{!! $recipe ? 'Fine-tune your <em>recipe</em>' : 'Write it <em>down</em>' !!}</h1>
        @if ($recipe)
            <p class="editor__status">
                <x-ui.chip :tone="$recipe->isComplete() ? 'secondary' : 'subtle'">{{ $recipe->status->label() }}</x-ui.chip>
                <x-ui.chip>{{ $recipe->visibility->label() }}</x-ui.chip>
                <a href="{{ route('recipes.show', $recipe) }}" wire:navigate>View recipe</a>
            </p>
        @endif
    </header>

    <form class="stack stack--lg" wire:submit="save" novalidate>
        <x-ui.card title="Basics">
            <div class="stack">
                <x-ui.field name="form.title" label="Title" maxlength="80" required wire:model="form.title" placeholder="Grandma's chicken soup" />
                <x-ui.field name="form.summary" label="Short description" as="textarea" maxlength="280" hint="Up to 280 characters. Shown on recipe cards." wire:model="form.summary" />
                <div class="form-row">
                    <x-ui.field name="form.meal_type" label="Meal" as="select" placeholder="Choose a meal" :options="MealType::options()" wire:model="form.meal_type" />
                    <x-ui.field name="form.cuisine" label="Cuisine" as="select" placeholder="Choose a cuisine" :options="Cuisine::options()" wire:model="form.cuisine" />
                </div>
                <div class="form-row">
                    <x-ui.field name="form.method" label="Cooking method" as="select" placeholder="Choose a method" :options="Method::options()" wire:model.live="form.method" />
                    <x-ui.field name="form.difficulty" label="Difficulty" as="select" placeholder="Choose a level" :options="Difficulty::options()" wire:model.live="form.difficulty" />
                </div>
                <div class="form-row form-row--4">
                    <x-ui.field name="form.prep_minutes" label="Prep (min)" type="number" min="0" max="2880" inputmode="numeric" wire:model.live.blur="form.prep_minutes" />
                    <x-ui.field name="form.cook_minutes" label="Cook (min)" type="number" min="0" max="2880" inputmode="numeric" wire:model.live.blur="form.cook_minutes" />
                    <x-ui.field name="form.servings" label="Servings" type="number" min="1" max="99" inputmode="numeric" wire:model="form.servings" />
                    <x-ui.field name="form.calories" label="Calories" type="number" min="0" max="9999" inputmode="numeric" hint="Per serving." wire:model="form.calories" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Photo">
            <div class="photo-editor">
                @if ($photo && ! $errors->has('photo') && $photo->isPreviewable())
                    <img class="photo-editor__preview" src="{{ $photo->temporaryUrl() }}" alt="Preview of the new photo">
                @elseif ($recipe?->image_path && ! $removePhoto)
                    <img class="photo-editor__preview" src="{{ $recipe->imageUrl(800) }}" alt="Current photo">
                @else
                    <div class="photo-editor__empty" aria-hidden="true">📷</div>
                @endif
                <div class="stack stack--sm">
                    <label class="field__label" for="photo">{{ $recipe?->image_path ? 'Replace the photo' : 'Add a photo' }}</label>
                    <input id="photo" type="file" accept="image/jpeg,image/png,image/webp" wire:model="photo" aria-describedby="photo-hint{{ $errors->has('photo') ? ' photo-error' : '' }}">
                    <p class="field__hint" id="photo-hint">JPG, PNG or WebP, up to 8 MB. Landscape photos look best.</p>
                    <p class="field__hint" wire:loading wire:target="photo">Uploading…</p>
                    @error('photo')<p class="field__error" id="photo-error" role="alert">{{ $message }}</p>@enderror
                    @if ($recipe?->image_path)
                        <x-ui.checkbox name="removePhoto" label="Remove the current photo" wire:model.live="removePhoto" />
                    @endif
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Ingredients">
            <p class="muted editor__help">Drag the handle or use the arrows to reorder. Add a section name, such as "For the sauce", to group ingredients.</p>
            <ol class="rows" wire:sort="moveIngredient" aria-label="Ingredients">
                @foreach ($form->ingredients as $index => $row)
                    <li class="row" wire:key="ingredient-{{ $row['key'] }}" wire:sort:item="{{ $row['key'] }}">
                        <button type="button" class="row__handle" wire:sort:handle aria-hidden="true" tabindex="-1"><x-ui.icon name="grip" /></button>
                        <div class="row__fields row__fields--ingredient">
                            <x-ui.field name="form.ingredients.{{ $index }}.quantity" label="Amount" placeholder="1 1/2" wire:model="form.ingredients.{{ $index }}.quantity" />
                            <x-ui.field name="form.ingredients.{{ $index }}.unit" label="Unit" placeholder="cups" wire:model="form.ingredients.{{ $index }}.unit" />
                            <x-ui.field name="form.ingredients.{{ $index }}.name" label="Ingredient" placeholder="flour" wire:model="form.ingredients.{{ $index }}.name" />
                            <x-ui.field name="form.ingredients.{{ $index }}.note" label="Note" placeholder="sifted" wire:model="form.ingredients.{{ $index }}.note" />
                            <x-ui.field name="form.ingredients.{{ $index }}.section" label="Section" placeholder="Optional" wire:model="form.ingredients.{{ $index }}.section" />
                        </div>
                        <div class="row__actions">
                            <x-ui.button variant="ghost" size="sm" icon="arrow-up" label="Move ingredient {{ $index + 1 }} up" wire:click="moveIngredient('{{ $row['key'] }}', {{ $index - 1 }})" :disabled="$index === 0" />
                            <x-ui.button variant="ghost" size="sm" icon="arrow-down" label="Move ingredient {{ $index + 1 }} down" wire:click="moveIngredient('{{ $row['key'] }}', {{ $index + 1 }})" :disabled="$loop->last" />
                            <x-ui.button variant="ghost" size="sm" icon="trash" label="Remove ingredient {{ $index + 1 }}" wire:click="removeIngredient('{{ $row['key'] }}')" />
                        </div>
                    </li>
                @endforeach
            </ol>
            @error('form.ingredients')<p class="field__error" role="alert">{{ $message }}</p>@enderror
            <x-ui.button variant="secondary" size="sm" icon="plus" wire:click="addIngredient" :disabled="count($form->ingredients) >= RecipeForm::MAX_INGREDIENTS">Add ingredient</x-ui.button>
        </x-ui.card>

        <x-ui.card title="Steps">
            <ol class="rows" wire:sort="moveStep" aria-label="Steps">
                @foreach ($form->steps as $index => $row)
                    <li class="row" wire:key="step-{{ $row['key'] }}" wire:sort:item="{{ $row['key'] }}">
                        <button type="button" class="row__handle" wire:sort:handle aria-hidden="true" tabindex="-1"><x-ui.icon name="grip" /></button>
                        <div class="row__fields">
                            <x-ui.field name="form.steps.{{ $index }}.body" label="Step {{ $index + 1 }}" as="textarea" maxlength="2000" wire:model="form.steps.{{ $index }}.body" />
                        </div>
                        <div class="row__actions">
                            <x-ui.button variant="ghost" size="sm" icon="arrow-up" label="Move step {{ $index + 1 }} up" wire:click="moveStep('{{ $row['key'] }}', {{ $index - 1 }})" :disabled="$index === 0" />
                            <x-ui.button variant="ghost" size="sm" icon="arrow-down" label="Move step {{ $index + 1 }} down" wire:click="moveStep('{{ $row['key'] }}', {{ $index + 1 }})" :disabled="$loop->last" />
                            <x-ui.button variant="ghost" size="sm" icon="trash" label="Remove step {{ $index + 1 }}" wire:click="removeStep('{{ $row['key'] }}')" />
                        </div>
                    </li>
                @endforeach
            </ol>
            <x-ui.button variant="secondary" size="sm" icon="plus" wire:click="addStep" :disabled="count($form->steps) >= RecipeForm::MAX_STEPS">Add step</x-ui.button>
        </x-ui.card>

        <x-ui.card title="Extras">
            <div class="stack">
                <fieldset class="choices">
                    <legend class="field__label">Collections</legend>
                    <p class="field__hint">We tick Quick &amp; Easy and Slow Cook for you when the details fit.</p>
                    <div class="choices__grid">
                        @foreach (Collection::options() as $value => $label)
                            <x-ui.checkbox name="collection-{{ Str::slug($value) }}" :label="$label" value="{{ $value }}" wire:model.live="form.collections" />
                        @endforeach
                    </div>
                </fieldset>
                <fieldset class="choices">
                    <legend class="field__label">Dietary</legend>
                    <div class="choices__grid">
                        @foreach (DietaryTag::options() as $value => $label)
                            <x-ui.checkbox name="diet-{{ Str::slug($value) }}" :label="$label" value="{{ $value }}" wire:model="form.dietary_tags" />
                        @endforeach
                    </div>
                </fieldset>
                <x-ui.field name="form.occasion" label="Occasion" as="select" placeholder="Any time" :options="Occasion::options()" wire:model="form.occasion" />
                <x-ui.field name="form.notes" label="Tips and notes" as="textarea" maxlength="2000" placeholder="Swaps, storage, serving ideas…" wire:model="form.notes" />
                <x-ui.field name="form.video_url" label="Video link" type="url" placeholder="https://www.youtube.com/watch?v=…" hint="A YouTube or Vimeo link." wire:model="form.video_url" />
            </div>
        </x-ui.card>

        <x-ui.card title="Who can see it?">
            <fieldset class="visibility" aria-describedby="visibility-help">
                <legend class="visually-hidden">Visibility</legend>
                @foreach (Visibility::cases() as $option)
                    <label class="visibility__option">
                        <input type="radio" name="visibility" value="{{ $option->value }}" wire:model="form.visibility">
                        <span>
                            <strong>{{ $option->label() }}</strong>
                            <span class="muted">{{ $option->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>
            <p class="field__hint" id="visibility-help">
                @if (! auth()->user()->hasVerifiedEmail())
                    Confirm your email address to share recipes. Until then they stay private.
                @else
                    Recipes need at least one ingredient and one step before you can share them.
                @endif
            </p>
            @error('form.visibility')<p class="field__error" role="alert">{{ $message }}</p>@enderror

            @if ($recipe?->visibility === Visibility::Unlisted)
                <div class="share-link" x-data="{ copied: false }">
                    <label class="field__label" for="share-link">Share link</label>
                    <div class="share-link__row">
                        <input class="field__control" id="share-link" type="text" readonly value="{{ $recipe->shareUrl() }}" x-ref="link">
                        <x-ui.button variant="secondary" x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                        </x-ui.button>
                    </div>
                    <x-ui.button variant="ghost" size="sm" icon="reset" wire:click="resetShareLink" wire:confirm="Make a new link? The old link will stop working.">Reset link</x-ui.button>
                </div>
            @endif
        </x-ui.card>

        <div class="editor__actions">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ $recipe ? 'Save changes' : 'Save recipe' }}</x-ui.button>
            <span class="field__hint" wire:loading wire:target="save">Saving…</span>
        </div>
    </form>

    @if ($recipe)
        <x-ui.card title="Delete recipe" class="danger-zone">
            <div class="stack">
                <p>This permanently deletes the recipe and its photo. It can't be undone.</p>
                <div>
                    <x-ui.button variant="danger" icon="trash" wire:click="delete" wire:confirm="Delete “{{ $recipe->title }}” for good?">Delete recipe</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endif
</div>
