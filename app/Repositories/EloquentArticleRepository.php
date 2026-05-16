<?php

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function all()
    {
        return Article::all();
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
        return Article::trending()->get();
    }

    public function create(array $data): Article
    {
        /** @var Article $article */
        $article = Article::create($data);

        return $article;
    }
}
