<?php

namespace App\Repositories;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\TrendingArticle;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function all()
    {
        return Article::with(['user', 'tags'])->paginate(10);
    }

    public function findById(int $id)
    {
        return Article::findOrFail($id);
    }

    public function getPublished()
    {
        return Article::published()->get();
    }

    public function getByAuthor(int $userId)
    {
        return Article::byAuthor($userId)->get();
    }

    public function getTrending()
    {
        return TrendingArticle::with('article')
            ->orderBy('trending_score', 'desc')
            ->get()
            ->pluck('article');
    }

    public function create(array $data): Article
    {
        /** @var Article $article */
        $article = Article::create($data);

        if ($article->status == ArticleStatus::PUBLISHED) {
            ArticlePublished::dispatch($article);
        }

        return $article;
    }

    public function update(int $id, array $data): Article
    {
        /** @var Article $article */
        $article = Article::findOrFail($id);
        $article->update($data);

        return $article;
    }

    public function calculateTrendingArticles(int $limit = 50): void
    {
        $trendingResults = [];

        $oneWeekAgo = now()->subDays(7);

        Article::published()
            ->withCount([
                'likes' => function ($query) use ($oneWeekAgo) {
                    $query->where('likes.created_at', '>=', $oneWeekAgo);
                },
                'comments' => function ($query) use ($oneWeekAgo) {
                    $query->where('comments.created_at', '>=', $oneWeekAgo);
                },
            ])
            ->with(['user' => function ($query) use ($oneWeekAgo) {
                $query->withCount([
                    'followers' => function ($q) use ($oneWeekAgo) {
                        $q->where('users.created_at', '>=', $oneWeekAgo);
                    },
                ]);
            }])
            ->chunk(200, function ($articles) use (&$trendingResults) {
                /** @var Article $article */
                foreach ($articles as $article) {

                    $likesCount = $article->likes_count;
                    $commentsCount = $article->comments_count;
                    $authorNewFollowersCount = $article->user ? $article->user->followers_count : 0;

                    $score = ($likesCount * 2) + ($commentsCount * 4) + ($authorNewFollowersCount * 2);

                    if ($score > 0) {
                        $trendingResults[] = [
                            'article_id' => $article->id,
                            'trending_score' => $score,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            });

        usort($trendingResults, fn ($a, $b) => $b['trending_score'] <=> $a['trending_score']);

        $topResults = array_slice($trendingResults, 0, $limit);

        DB::transaction(function () use ($topResults) {
            TrendingArticle::query()->delete();
            if (! empty($topResults)) {
                TrendingArticle::insert($topResults);
            }
        });
    }

    public function pruneStaleDrafts(Carbon $staleDate, int $chunkSize, callable $callback): void
    {
        Article::where('status', ArticleStatus::DRAFT)
            ->where('updated_at', '<=', $staleDate)
            ->whereNull('deleted_at')
            ->chunkById($chunkSize, function ($articles) use ($callback) {

                $articleIds = $articles->pluck('id');

                $prunedCount = Article::whereIn('id', $articleIds)->delete();

                $callback($prunedCount);
            });
    }

    public function updateReadTimeQuietly(int $articleId, int $minutes): void
    {
        /** @var Article $article */
        $article = Article::findOrFail($articleId);
        $article->read_time = $minutes;
        $article->saveQuietly();
    }
}
