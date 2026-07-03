<?php

namespace App\Jobs;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PruneStaleDraftsJob implements ShouldQueue
{
    use Queueable;

    private const RETENTION_DAYS = 90;

    private const CHUNK_SIZE = 1000;

    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(ArticleRepositoryInterface $articleRepository): void
    {
        $totalPruned = 0;

        $staleDate = now()->subDays(self::RETENTION_DAYS);

        $articleRepository->pruneStaleDrafts(
            $staleDate,
            self::CHUNK_SIZE,
            function (int $prunedInChunk) use (&$totalPruned) {
                $totalPruned += $prunedInChunk;
            });

        Log::info('Stale drafts cleaned up successfully!', [
            'total_pruned_articles' => $totalPruned,
            'retention_period_days' => self::RETENTION_DAYS,
        ]);
    }
}
