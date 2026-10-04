<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:backup --type=daily')->dailyAt('02:30');
Schedule::command('app:backup --type=weekly')->weeklyOn(0, '03:00');
