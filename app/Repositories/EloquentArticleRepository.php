<?php

namespace App\Repositories;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\TrendingArticle;
use App\Repositories\Contracts\ArticleRepositoryInterface;
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
}
