<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ArticlePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];


    public function __construct(public Article $article) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        throw new \RuntimeException('Simulated outage');
        
        return (new MailMessage)
            ->line("This Article has been published {$this->article->title}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Article published notification failed permanently', [
            'job'       => static::class,
            'user_id'   => $this->article->user_id,
            'exception' => $exception->getMessage(),
            'trace'     => substr($exception->getTraceAsString(), 0, 500),
        ]);
    }
}
