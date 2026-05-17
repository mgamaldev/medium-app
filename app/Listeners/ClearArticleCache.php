<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearArticleCache implements ShouldHandleEventsAfterCommit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ArticlePublished $event): void
    {
        Cache::forget('Published articles');

        Log::info('Cache cleared for published articles');

    }
}
