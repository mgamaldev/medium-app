<?php

namespace App\Strategies\ContentVisibility;

use App\Enums\ArticleStatus;
use App\Strategies\Contracts\ContentVisibilityStrategy;
use InvalidArgumentException;

class ContentVisibilityResolver
{
    public static function resolve(ArticleStatus $status): ContentVisibilityStrategy
    {
        return match ($status) {
            ArticleStatus::PUBLISHED => new PublicVisibilityStrategy,
            ArticleStatus::FOLLOWERS_ONLY => new FollowersOnlyVisibilityStrategy,
            ArticleStatus::DRAFT => new PrivateDraftVisibilityStrategy,

            default => throw new InvalidArgumentException('Unknown article visibility status')
        };

    }
}
