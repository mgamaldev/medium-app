<?php

namespace App\Strategies\ContentVisibility;

use App\Models\User;
use App\Models\Article;
use App\Strategies\Contracts\ContentVisibilityStrategy;

class PublicVisibilityStrategy implements ContentVisibilityStrategy
{
    public function canView(?User $user, Article $article): bool
    {
        return true;
    }
    
}