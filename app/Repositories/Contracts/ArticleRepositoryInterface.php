<?php

namespace App\Repositories\Contracts;

use App\Models\Article;

interface ArticleRepositoryInterface
{
    public function all();

    public function findById(int $id);

    public function getPublished();

    public function getByAuthor(int $userId);

    public function getTrending();

    public function calculateTrendingArticles(int $limit = 50): void;

    public function create(array $data): Article;
}
