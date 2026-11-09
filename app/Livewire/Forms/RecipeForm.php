<?php

namespace App\Livewire\Forms;

use App\Enums\Collection;
use App\Enums\Cuisine;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\Method;
use App\Enums\Occasion;
use App\Enums\Visibility;
use App\Models\Recipe;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * The recipe editor's state. Ingredients and steps are kept as ordered rows; each row has a
 * stable `key` for the page and the row's UUID once it has been saved.
 */
class RecipeForm extends Form
{
    public const MAX_INGREDIENTS = 60;

    public const MAX_STEPS = 40;

    public string $title = '';

    public string $summary = '';

    public string $meal_type = '';

    public string $cuisine = '';

    public string $method = '';

    public string $difficulty = '';

    public string $occasion = '';

    public int|string|null $prep_minutes = null;

    public int|string|null $cook_minutes = null;

    public int|string|null $servings = null;

    public int|string|null $calories = null;

    public string $notes = '';

    public string $video_url = '';

    /** @var list<string> */
    public array $collections = [];

    /** @var list<string> */
    public array $dietary_tags = [];

    public string $visibility = 'private';

    /** @var list<array{key: string, uuid: string|null, section: string, quantity: string, unit: string, name: string, note: string}> */
    public array $ingredients = [];

    /** @var list<array{key: string, uuid: string|null, body: string}> */
    public array $steps = [];

    /**
     * Collections the editor ticked by rule.
     *
     * @var list<string>
     */
    public array $suggested = [];

    /**
     * Suggested collections the cook then unticked.
     *
     * @var list<string>
     */
    public array $dismissed = [];

    public function fillFrom(?Recipe $recipe): void
    {
        if ($recipe === null) {
            $this->addIngredient();
            $this->addStep();

            return;
        }

        $this->title = $recipe->title;
        $this->summary = (string) $recipe->summary;
        $this->meal_type = (string) $recipe->meal_type?->value;
        $this->cuisine = (string) $recipe->cuisine?->value;
        $this->method = (string) $recipe->method?->value;
        $this->difficulty = (string) $recipe->difficulty?->value;
        $this->occasion = (string) $recipe->occasion?->value;
        $this->prep_minutes = $recipe->prep_minutes;
        $this->cook_minutes = $recipe->cook_minutes;
        $this->servings = $recipe->servings;
        $this->calories = $recipe->calories;
        $this->notes = (string) $recipe->notes;
        $this->video_url = (string) $recipe->video_url;
        $this->collections = $recipe->collections?->map(fn (Collection $c) => $c->value)->values()->all() ?? [];
        $this->dietary_tags = $recipe->dietary_tags?->map(fn (DietaryTag $t) => $t->value)->values()->all() ?? [];
        $this->visibility = $recipe->visibility->value;

        $this->ingredients = $recipe->ingredients->map(fn ($row) => [
            'key' => $row->uuid,
            'uuid' => $row->uuid,
            'section' => (string) $row->section,
            'quantity' => (string) $row->quantity,
            'unit' => (string) $row->unit,
            'name' => $row->name,
            'note' => (string) $row->note,
        ])->values()->all();

        $this->steps = $recipe->steps->map(fn ($row) => [
            'key' => $row->uuid,
            'uuid' => $row->uuid,
            'body' => $row->body,
        ])->values()->all();

        if ($this->ingredients === []) {
            $this->addIngredient();
        }

        if ($this->steps === []) {
            $this->addStep();
        }
    }

    public function addIngredient(): void
    {
        if (count($this->ingredients) < self::MAX_INGREDIENTS) {
            $this->ingredients[] = ['key' => Str::random(10), 'uuid' => null, 'section' => '', 'quantity' => '', 'unit' => '', 'name' => '', 'note' => ''];
        }
    }

    public function addStep(): void
    {
        if (count($this->steps) < self::MAX_STEPS) {
            $this->steps[] = ['key' => Str::random(10), 'uuid' => null, 'body' => ''];
        }
    }

    /**
     * Moves the row with this key to a new position in the list.
     *
     * @param  'ingredients'|'steps'  $list
     */
    public function move(string $list, string $key, int $position): void
    {
        $rows = $this->{$list};
        $from = collect($rows)->search(fn (array $row) => $row['key'] === $key);

        if ($from === false) {
            return;
        }

        $row = array_splice($rows, $from, 1)[0];
        array_splice($rows, max(0, min($position, count($rows))), 0, [$row]);

        $this->{$list} = $rows;
    }

    /**
     * @param  'ingredients'|'steps'  $list
     */
    public function remove(string $list, string $key): void
    {
        $this->{$list} = array_values(array_filter($this->{$list}, fn (array $row) => $row['key'] !== $key));

        if ($this->{$list} === []) {
            $list === 'ingredients' ? $this->addIngredient() : $this->addStep();
        }
    }

    /**
     * Ticks collections whose rule now fits, unless the cook unticked them earlier.
     *
     * @return list<string> the collections just ticked
     */
    public function applySuggestions(): array
    {
        $total = (int) $this->prep_minutes + (int) $this->cook_minutes;
        $ticked = [];

        foreach (Collection::cases() as $collection) {
            $value = $collection->value;
            $fits = $collection->suggests($total ?: null, Difficulty::tryFrom($this->difficulty), Method::tryFrom($this->method));

            if ($fits && ! in_array($value, $this->collections, true) && ! in_array($value, $this->dismissed, true)) {
                $this->collections[] = $value;
                $this->suggested[] = $value;
                $ticked[] = $value;
            }
        }

        return $ticked;
    }

    /**
     * Remembers suggestions the cook has unticked, so they aren't ticked again.
     */
    public function noteUnticked(): void
    {
        foreach ($this->suggested as $value) {
            if (! in_array($value, $this->collections, true) && ! in_array($value, $this->dismissed, true)) {
                $this->dismissed[] = $value;
            }
        }
    }

    /**
     * Rows with nothing typed in them are ignored, so empty spare rows never fail validation.
     */
    public function dropBlankRows(): void
    {
        $this->ingredients = array_values(array_filter($this->ingredients, fn (array $row) => trim($row['name'].$row['quantity'].$row['unit'].$row['note'].$row['section']) !== ''));
        $this->steps = array_values(array_filter($this->steps, fn (array $row) => trim($row['body']) !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minutes = ['nullable', 'integer', 'min:0', 'max:2880'];

        return [
            'title' => ['required', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:280'],
            'meal_type' => ['nullable', Rule::enum(MealType::class)],
            'cuisine' => ['nullable', Rule::enum(Cuisine::class)],
            'method' => ['nullable', Rule::enum(Method::class)],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'occasion' => ['nullable', Rule::enum(Occasion::class)],
            'prep_minutes' => $minutes,
            'cook_minutes' => $minutes,
            'servings' => ['nullable', 'integer', 'min:1', 'max:99'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'video_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && (new Recipe(['video_url' => $value]))->videoEmbedUrl() === null) {
                    $fail('Paste a YouTube or Vimeo link, such as https://www.youtube.com/watch?v=…');
                }
            }],
            'collections' => ['array'],
            'collections.*' => [Rule::enum(Collection::class)],
            'dietary_tags' => ['array'],
            'dietary_tags.*' => [Rule::enum(DietaryTag::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'ingredients' => ['array', 'max:'.self::MAX_INGREDIENTS],
            'ingredients.*.uuid' => ['nullable', 'string'],
            'ingredients.*.section' => ['nullable', 'string', 'max:80'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:20'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:30'],
            'ingredients.*.name' => ['required', 'string', 'max:120'],
            'ingredients.*.note' => ['nullable', 'string', 'max:120'],
            'steps' => ['array', 'max:'.self::MAX_STEPS],
            'steps.*.uuid' => ['nullable', 'string'],
            'steps.*.body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ingredients.*.name.required' => 'Give this ingredient a name.',
            'steps.*.body.required' => 'Describe this step.',
        ];
    }

    /**
     * The recipe's own fields, ready for SaveRecipe.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        $nullable = fn (mixed $value) => blank($value) ? null : $value;

        return [
            'title' => trim($this->title),
            'summary' => $nullable(trim($this->summary)),
            'meal_type' => $nullable($this->meal_type),
            'cuisine' => $nullable($this->cuisine),
            'method' => $nullable($this->method),
            'difficulty' => $nullable($this->difficulty),
            'occasion' => $nullable($this->occasion),
            'prep_minutes' => $nullable($this->prep_minutes),
            'cook_minutes' => $nullable($this->cook_minutes),
            'servings' => $nullable($this->servings),
            'calories' => $nullable($this->calories),
            'notes' => $nullable(trim($this->notes)),
            'video_url' => $nullable(trim($this->video_url)),
            'collections' => array_values(array_unique($this->collections)),
            'dietary_tags' => array_values(array_unique($this->dietary_tags)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function ingredientRows(): array
    {
        $trim = fn (string $value) => trim($value) === '' ? null : trim($value);

        return array_map(fn (array $row) => [
            'uuid' => $row['uuid'],
            'section' => $trim($row['section']),
            'quantity' => $trim($row['quantity']),
            'unit' => $trim($row['unit']),
            'name' => trim($row['name']),
            'note' => $trim($row['note']),
        ], $this->ingredients);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stepRows(): array
    {
        return array_map(fn (array $row) => ['uuid' => $row['uuid'], 'body' => trim($row['body'])], $this->steps);
    }
}
