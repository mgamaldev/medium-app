<?php

namespace App\Strategies\ContentVisibility;

use App\Models\User;
use App\Models\Article;
use App\Strategies\Contracts\ContentVisibilityStrategy;

class PrivateDraftVisibilityStrategy implements ContentVisibilityStrategy
{
    public function canView(?User $user, Article $article): bool
    {
        if(!$user)
        {
            return false;
        }

        return $user->id === $article->user_id;
    }
    
}