<?php

namespace App\Actions;

use App\Enums\RecipeStatus;
use App\Enums\Visibility;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RecipeImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a recipe with its ingredients and steps in one transaction. The input
 * is already validated and authorized by the editor.
 */
final class SaveRecipe
{
    /**
     * @param  array<string, mixed>  $details  Fillable recipe fields.
     * @param  list<array{uuid?: string|null, section?: string|null, quantity?: string|null, unit?: string|null, name: string, note?: string|null}>  $ingredients
     * @param  list<array{uuid?: string|null, body: string}>  $steps
     */
    public function handle(
        User $user,
        ?Recipe $recipe,
        array $details,
        array $ingredients,
        array $steps,
        Visibility $visibility,
        ?UploadedFile $photo = null,
        bool $removePhoto = false,
    ): Recipe {
        // Image work happens before the transaction, so a slow resize never holds locks.
        $newImage = $photo ? RecipeImages::store($photo) : null;
        $recipe ??= new Recipe;
        $oldImage = $recipe->image_path;

        DB::transaction(function () use ($user, $recipe, $details, $ingredients, $steps, $visibility, $newImage, $removePhoto) {
            $complete = $ingredients !== [] && $steps !== [];

            $recipe->fill($details);
            $recipe->forceFill([
                'user_id' => $recipe->user_id ?? $user->id,
                'status' => $complete ? RecipeStatus::Complete : RecipeStatus::Draft,
                // Drafts can't be shared, whatever was asked for.
                'visibility' => $complete ? $visibility : Visibility::Private,
            ]);

            if ($recipe->visibility === Visibility::Public) {
                $recipe->published_at ??= now();
            }

            if ($newImage !== null) {
                $recipe->image_path = $newImage;
            } elseif ($removePhoto) {
                $recipe->image_path = null;
            }

            $recipe->save();

            $this->sync($recipe, 'ingredients', array_map(fn (array $row) => [
                'uuid' => $row['uuid'] ?? null,
                'section' => $row['section'] ?? null,
                'quantity' => $row['quantity'] ?? null,
                'unit' => $row['unit'] ?? null,
                'name' => $row['name'],
                'note' => $row['note'] ?? null,
            ], $ingredients));

            $this->sync($recipe, 'steps', array_map(fn (array $row) => [
                'uuid' => $row['uuid'] ?? null,
                'body' => $row['body'],
            ], $steps));
        });

        if ($oldImage !== null && $oldImage !== $recipe->image_path) {
            RecipeImages::delete($oldImage);
        }

        return $recipe->refresh();
    }

    /**
     * Replaces a recipe's rows: updates the ones it keeps (matched on UUID), creates new ones,
     * deletes the rest, and numbers them in order.
     *
     * @param  'ingredients'|'steps'  $relation
     * @param  list<array<string, mixed>>  $rows
     */
    private function sync(Recipe $recipe, string $relation, array $rows): void
    {
        $existing = $recipe->{$relation}()->get()->keyBy('uuid');
        $kept = [];

        foreach ($rows as $index => $row) {
            $uuid = $row['uuid'];
            unset($row['uuid']);
            $row['position'] = $index + 1;

            if ($uuid !== null && $existing->has($uuid)) {
                $existing[$uuid]->update($row);
                $kept[] = $uuid;
            } else {
                $recipe->{$relation}()->create($row);
            }
        }

        $removed = $existing->keys()->diff($kept)->all();

        if ($removed !== []) {
            $recipe->{$relation}()->whereIn('uuid', $removed)->delete();
        }
    }
}
