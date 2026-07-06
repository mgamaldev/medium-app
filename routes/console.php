<?php

use App\Jobs\CalculateTrendingArticlesJob;
use App\Jobs\PruneStaleDraftsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('digests:dispatch')->weeklyOn(1, '09:00');

Schedule::job(CalculateTrendingArticlesJob::class)->dailyAt('02:00')->withoutOverlapping()->onOneServer();

Schedule::job(PruneStaleDraftsJob::class)->weeklyOn(7, '03:30')->withoutOverlapping();
