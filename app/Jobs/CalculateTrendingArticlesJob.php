<?php

namespace App\Jobs;

use App\Models\TrendingArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateTrendingArticlesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected TrendingArticle $trendingArticle) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $trendingScore = $this->trendingArticle->article->likes_count + $this->trendingArticle->article->comments_count;

        $this->trendingArticle->update([
            'trending_score' => $trendingScore,
        ]);

    }
}
