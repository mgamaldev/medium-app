<?php

namespace App\Strategies\ContentVisibility;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Strategies\Contracts\ContentVisibilityStrategy;
use InvalidArgumentException;

class ContentVisibilityResolver
{
    public function resolve(Article $article): ContentVisibilityStrategy
    {
        return match ($article->status) {
            ArticleStatus::PUBLISHED->value => new PublicVisibilityStrategy,
            ArticleStatus::FOLLOWERS_ONLY->value => new FollowersOnlyVisibilityStrategy,
            ArticleStatus::DRAFT->value => new PrivateDraftVisibilityStrategy,

            default => throw new InvalidArgumentException('Unknown article visibility status')
        };

    }
}
