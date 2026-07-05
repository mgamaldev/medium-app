<?php

use App\Jobs\CalculateTrendingArticlesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('digests:dispatch')->weeklyOn(1, '09:00');

Schedule::command('drafts:cleanup')->weeklyOn(7, '03:30')->withoutOverlapping();

Schedule::job(CalculateTrendingArticlesJob::class)->dailyAt('02:00')->withoutOverlapping()->onOneServer();
