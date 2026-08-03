<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendArticlePublishedNotification implements ShouldQueue
{
    public function handle(ArticlePublished $event): void
    {
        Notification::send(
            $event->article->user->followers,
            new ArticlePublishedNotification($event->article)
        );
    }
}
