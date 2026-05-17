<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class SendAuthorNotification implements ShouldHandleEventsAfterCommit
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
        $author = $event->article->user;

        Log::info("Notification sent to author {$author}");
    }
}
