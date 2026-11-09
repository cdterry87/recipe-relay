<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auth:clear-resets')->hourly();

// Each member's digest goes out at their chosen day and hour, in their own timezone.
Schedule::command('digest:send')->hourly()->withoutOverlapping()->onOneServer();
