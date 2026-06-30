<?php

namespace App\Jobs;

<<<<<<< HEAD
use App\Enums\ArticleStatus;
=======
>>>>>>> 4d4f3e1 (Add sendWeeklyDigestJob with all related functions and files)
use App\Mail\WeeklyDigestMail;
use App\Models\Article;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigestJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $subscriber) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $articles = Article::query()->where('created_at', '>=', now()->subDays(7))
            ->orderByRaw('likes_count + comments_count desc')
            ->limit(5)
            ->get();

        Mail::to($this->subscriber->email)->send(new WeeklyDigestMail($articles));
    }
}
