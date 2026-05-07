<?php

namespace App\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;

class ArticleFactory 
{
    public function create(string $type, array $data): Article
    {

        return match($type)
        {
            'draft' => $this->createDraft($data),
            'published' => $this->createPublished($data),
            'featured' => $this->createFeatured($data),
            default => throw new \Exception("UnKnown Article type")
        };

    }

    private function createDraft(array $data):Article
    {
        $this->validateData($data);
        
        $data['status'] = ArticleStatus::DRAFT;

        return Article::create($data);
    }

    private function createPublished(array $data):Article
    {
        $this->validateData($data);

        $data['status'] = ArticleStatus::PUBLISHED;
        $data['published_at'] = now();

        return Article::create($data);
    }

    private function createFeatured(array $data):Article
    {
        $this->validateData($data);

        if (strlen($data['title']) < 50 )
        {
            throw new \Exception('Title must be at least 50 chars for featured articles');
        };

        $data['status'] = ArticleStatus::PUBLISHED;
        $data['is_featured'] = true;
        $data['published_at'] = now();

        return Article::create($data);
    }

    private function validateData(array $data)
    {
        if (empty($data['title']) || empty($data['body']))
        {
            throw new \Exception('Title and Body are required');
        }    
    }
}


?>