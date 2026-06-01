<?php

namespace App\Strategies\ContentVisibility;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Strategies\Contracts\ContentVisibilityStrategy;

class ContentVisibilityResolver
{
    public static function resolve(Article $article): ContentVisibilityStrategy
    {
        return match ($article->status) {
            ArticleStatus::DRAFT => new PrivateDraftVisibilityStrategy,
            ArticleStatus::ARCHIVED => new ArchivedVisibilityStrategy,
            ArticleStatus::PUBLISHED => match ($article->visibility) {
                ArticleVisibility::PUBLIC => new PublicVisibilityStrategy,
                ArticleVisibility::FOLLOWERS_ONLY => new FollowersOnlyVisibilityStrategy
            }
        };

    }
}
