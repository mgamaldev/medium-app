<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewFollowerNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;


    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function viaQueue(object $notifiable): string
    {
        return 'notifications';
    }

    public function __construct(public User $user, public User $follower) {}

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
            ->line("You got a new follower {$this->follower->username}");
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
        Log::error('New follower notification failed permanently', [
            'job'       => static::class,
            'user_id'   => $this->user->id,
            'exception' => $exception->getMessage(),
            'trace'     => substr($exception->getTraceAsString(), 0, 500),
        ]);
    }
}
