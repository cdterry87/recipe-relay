<?php

namespace App\Support;

/**
 * Sample content for the home page while recipes and creators are still being built.
 * The recipes and photos come from the launch seed data (section B9 of docs/spec.md);
 * the creators are fictional. Milestones M3 and M4 replace this with real queries.
 */
final class Showcase
{
    /**
     * @return list<array{name: string, specialty: string, saves: string, recipes: int}>
     */
    public static function creators(): array
    {
        return [
            ['name' => 'Rosa Delgado', 'specialty' => 'Weeknight dinners', 'saves' => '4.8K', 'recipes' => 24],
            ['name' => 'Sam Okafor', 'specialty' => 'Big breakfasts', 'saves' => '3.1K', 'recipes' => 17],
            ['name' => 'Mei Tanaka', 'specialty' => 'Noodles and bowls', 'saves' => '2.6K', 'recipes' => 21],
            ['name' => 'Priya Shah', 'specialty' => 'Desserts', 'saves' => '1.9K', 'recipes' => 12],
        ];
    }

    /**
     * @return list<array{title: string, tag: string, image: string, minutes: int, calories: int, saves: string, by: string}>
     */
    public static function recipes(): array
    {
        return [
            ['title' => 'Fluffy Pancakes', 'tag' => 'Breakfast', 'image' => 'pancakes', 'minutes' => 15, 'calories' => 300, 'saves' => '1.4K', 'by' => 'Sam Okafor'],
            ['title' => 'Chicken Tikka Masala', 'tag' => 'Dinner', 'image' => 'chicken-tikka-masala', 'minutes' => 60, 'calories' => 700, 'saves' => '1.2K', 'by' => 'Rosa Delgado'],
            ['title' => 'Pork Ramen', 'tag' => 'Lunch', 'image' => 'pork-ramen', 'minutes' => 70, 'calories' => 600, 'saves' => '980', 'by' => 'Mei Tanaka'],
            ['title' => 'Chocolate Cake', 'tag' => 'Dessert', 'image' => 'chocolate-cake', 'minutes' => 75, 'calories' => 350, 'saves' => '940', 'by' => 'Priya Shah'],
            ['title' => 'Beef Tacos', 'tag' => 'Quick & Easy', 'image' => 'beef-tacos', 'minutes' => 30, 'calories' => 500, 'saves' => '870', 'by' => 'Rosa Delgado'],
            ['title' => 'Grilled Salmon', 'tag' => 'Dinner', 'image' => 'grilled-salmon', 'minutes' => 20, 'calories' => 400, 'saves' => '760', 'by' => 'Rosa Delgado'],
            ['title' => 'Pineapple Fried Rice', 'tag' => 'Side', 'image' => 'pineapple-fried-rice', 'minutes' => 20, 'calories' => 350, 'saves' => '690', 'by' => 'Mei Tanaka'],
            ['title' => 'Chicken Caesar Salad', 'tag' => 'Salad', 'image' => 'chicken-caesar-salad', 'minutes' => 20, 'calories' => 400, 'saves' => '610', 'by' => 'Sam Okafor'],
        ];
    }

    /**
     * Formats minutes the way cards show them, such as "20 min" or "1h 15m".
     */
    public static function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? "{$hours}h" : "{$hours}h {$rest}m";
    }
}
