<?php

namespace App\Jobs;

use App\Enums\ArticleStatus;
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
    public function __construct(public User $subscriber) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $articles = Article::query()
            ->where('published_at', '>=', now()->subDays(7))
            ->where('status', ArticleStatus::PUBLISHED)
            ->withCount(['likes', 'comments'])
            ->orderByRaw('likes_count + comments_count desc')
            ->limit(5)
            ->get();

        Mail::to($this->subscriber->email)->send(new WeeklyDigestMail($articles));
    }
}
