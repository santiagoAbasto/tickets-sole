<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SLA watchdog: alerts on tickets due soon / overdue and escalates breaches.
Schedule::command('tickets:check-sla')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
