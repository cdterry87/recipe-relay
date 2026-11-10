<?php

namespace Database\Seeders;

use App\Support\DemoWorld;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Builds the same launch content as `php artisan demo:reset`: the sample creators and seed
     * recipes always, and the demo account when DEMO_EMAIL and DEMO_PASSWORD are set.
     * Seeders never contain real people or fixed passwords.
     */
    public function run(): void
    {
        $result = DemoWorld::reset();

        $this->command?->info("Seeded {$result['creators']} sample creators and {$result['recipes']} recipes.");
    }
}
