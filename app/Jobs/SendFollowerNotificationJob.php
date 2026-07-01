<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFollowerNotificationJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        private User $user,
        private User $follower
    ) {}

    public function handle(): void
    {
        $this->user->notify(new NewFollowerNotification($this->user, $this->follower));
    }

    public function failed(Throwable $exception)
    {
        Log::error('Follower notification failed permanently', [
            'user_id' => $this->user->id,
            'follower_id' => $this->follower->id,
            'message' => $exception->getMessage(),
            'context' => 'follower_notification',
        ]);
    }
}
