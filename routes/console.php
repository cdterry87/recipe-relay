<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auth:clear-resets')->hourly();

// Each member's digest goes out at their chosen day and hour, in their own timezone.
Schedule::command('digest:send')->hourly()->withoutOverlapping()->onOneServer();

// In demo mode, the demo world is restored every night at midnight in the demo timezone.
Schedule::command('demo:reset')
    ->dailyAt('00:00')
    ->timezone((string) config('relay.demo.timezone', 'UTC'))
    ->when(fn () => (bool) config('relay.demo_mode'))
    ->withoutOverlapping()
    ->onOneServer();
