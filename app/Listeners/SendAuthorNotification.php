<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendAuthorNotification
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
        /** @var User $author */
        $author = $event->article->user;

        Log::info("Notification sent to author {$author}");
    }
}
