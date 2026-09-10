<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping() prevents two scheduled runs from ever executing at
// once, which matters here because PO-number generation reads a same-day
// count with no row lock - two concurrent runs could compute the same
// po_number and abort mid-run on the resulting unique-constraint violation.
Schedule::command('app:check-replenishment')->dailyAt('06:00')->withoutOverlapping();
