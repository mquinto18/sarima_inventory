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
// This matters even more now that it runs every 15 minutes instead of once
// a day, since a slow run could otherwise still be going when the next one
// is due.
Schedule::command('app:check-replenishment')->everyFifteenMinutes()->withoutOverlapping();

// Daily is sufficient here: a product only crosses "expired for 1+ day" once
// per calendar day. withoutOverlapping() for the same reason as above - this
// also mutates stock, so two concurrent runs must never interleave.
Schedule::command('app:dispose-expired-stock')->dailyAt('01:00')->withoutOverlapping();

// Refits each product's SARIMAX forecast (demand + reorder point) and caches
// it for a day (see ProductController::warmSarimaxForecast()). Deliberately
// NOT run via the 15-minute replenishment check or any other web-triggered
// path - each fit spawns a Python subprocess and takes a few seconds per
// product, so it only ever runs here, off the request cycle.
// withoutOverlapping() since a slow run (dozens of products x a few seconds
// each) could still be going at the next scheduled time.
Schedule::command('app:warm-sarimax-reorder-points')->dailyAt('02:00')->withoutOverlapping();
