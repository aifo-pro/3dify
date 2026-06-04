<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily run of saved-searches alerts.
Schedule::command('saved-searches:run')->dailyAt('07:30')->withoutOverlapping();

// Webhook fallback: poll Didit for pending KYC verifications every 10 minutes.
Schedule::command('kyc:sync-pending')->everyTenMinutes()->withoutOverlapping();
