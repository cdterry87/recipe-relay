<?php

namespace App\Console\Commands;

use App\Support\DemoWorld;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:reset')]
#[Description('Create or restore the sample creators, seed recipes and demo account')]
class DemoResetCommand extends Command
{
    public function handle(): int
    {
        try {
            $result = DemoWorld::reset();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Restored {$result['creators']} sample creators and {$result['recipes']} seed recipes.");

        if ($result['demo'] === null) {
            $this->components->warn('DEMO_EMAIL and DEMO_PASSWORD are not set, so there is no demo account.');
        } else {
            $this->components->info("The demo account {$result['demo']->email} has been reset.");
        }

        return self::SUCCESS;
    }
}
