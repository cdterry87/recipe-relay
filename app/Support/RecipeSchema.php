<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;

/**
 * schema.org Recipe data for search engines. Only ever built for public recipes.
 */
final class RecipeSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Recipe $recipe): array
    {
        $duration = fn (?int $minutes) => $minutes ? 'PT'.$minutes.'M' : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'description' => $recipe->summary,
            'url' => route('recipes.show', $recipe),
            'image' => $recipe->imageUrl(1600),
            'author' => ['@type' => 'Person', 'name' => $recipe->user->name],
            'datePublished' => $recipe->published_at?->toDateString(),
            'prepTime' => $duration($recipe->prep_minutes),
            'cookTime' => $duration($recipe->cook_minutes),
            'totalTime' => $duration($recipe->totalMinutes()),
            'recipeYield' => $recipe->servings ? $recipe->servings.' servings' : null,
            'recipeCategory' => $recipe->meal_type?->value,
            'recipeCuisine' => $recipe->cuisine?->value,
            'keywords' => $recipe->collections?->map->value->implode(', ') ?: null,
            'nutrition' => $recipe->calories ? ['@type' => 'NutritionInformation', 'calories' => $recipe->calories.' calories'] : null,
            'recipeIngredient' => $recipe->ingredients->map(fn (Ingredient $row) => trim(implode(' ', array_filter([$row->quantity, $row->unit, $row->name, $row->note ? "({$row->note})" : null]))))->all(),
            'recipeInstructions' => $recipe->steps->map(fn (Step $step) => ['@type' => 'HowToStep', 'text' => $step->body])->all(),
        ], fn ($value) => $value !== null && $value !== []);
    }
}
