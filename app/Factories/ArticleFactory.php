<?php

namespace App\Factories;

use App\Enums\ArticleStatus;
use App\Exceptions\ArticleTypeValidationException;
use App\Exceptions\CoverimageValidationException;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;

class ArticleFactory
{
    public function __construct(protected ArticleRepositoryInterface $articleRepo) {}

    public function create(string $type, array $data): Article
    {

        return match ($type) {
            'draft' => $this->createDraft($data),
            'published' => $this->createPublished($data),
            'featured' => $this->createFeatured($data),
            default => throw new ArticleTypeValidationException("this type '$type' is not a vaild atricle type")
        };

    }

    private function createDraft(array $data): Article
    {
        $this->validateData($data);

        $data['status'] = ArticleStatus::DRAFT;

        return $this->articleRepo->create($data);
    }

    private function createPublished(array $data): Article
    {
        $this->validateData($data);

        $data['status'] = ArticleStatus::PUBLISHED;
        $data['published_at'] = now();

        return $this->articleRepo->create($data);
    }

    private function createFeatured(array $data): Article
    {
        $this->validateData($data);

        if (empty($data['cover_image'])) {
            throw new CoverimageValidationException('cover_image should not be empty');
        }

        $data['status'] = ArticleStatus::PUBLISHED;
        $data['is_featured'] = true;
        $data['published_at'] = now();

        return $this->articleRepo->create($data);
    }

    private function validateData(array $data)
    {
        if (empty($data['title']) || empty($data['body'])) {
            throw new \Exception('Title and Body are required');
        }
    }
}
