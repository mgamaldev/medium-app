<?php

namespace App\Jobs;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EstimateReadTimeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $articleId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ArticleRepositoryInterface $articlerepository): void
    {
        $article = $articlerepository->findById($this->articleId);

        if ($article) {
            $cleanBody = strip_tags($article->body);
            $wordCount = str_word_count($cleanBody);
            $minutes = max(1, (int) ceil($wordCount / 200));

            $articlerepository->updateReadTimeQuietly($this->articleId, $minutes);
        }
    }
}
