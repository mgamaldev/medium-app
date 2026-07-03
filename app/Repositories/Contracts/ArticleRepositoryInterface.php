<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use Carbon\Carbon;

interface ArticleRepositoryInterface
{
    public function all();

    public function findById(int $id);

    public function getPublished();

    public function getByAuthor(int $userId);

    public function getTrending();

    public function create(array $data): Article;

    public function calculateTrendingArticles(int $limit = 50): void;

    public function pruneStaleDrafts(Carbon $staleDate, int $chunkSize, callable $callback): void;
}
