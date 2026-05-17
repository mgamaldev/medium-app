<?php

namespace App\Strategies\ContentVisibility;

use App\Models\Article;
use App\Models\User;
use App\Strategies\Contracts\ContentVisibilityStrategy;

class FollowersOnlyVisibilityStrategy implements ContentVisibilityStrategy
{
    public function canView(?User $user, Article $article): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->id === $article->user_id) {
            return true;
        }

        return $user->following()->where('user_id', $article->user_id)->exists();

    }
}
