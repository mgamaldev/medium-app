<?php

namespace App\Builders;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Builder;

class ArticleQueryBuilder extends Builder {

    public function published():static
    {
        return $this->where('status', ArticleStatus::PUBLISHED->value);
    }

    public function byAuthor(int $userId):static 
    {
        return $this->where('user_id', $userId);
    }

    public function withEngagementMetrics():static
    {
        return $this->withCount(['likes','comments']);

    }

    public function trending():static
    {
        return $this->published()->withEngagementMetrics()->orderByDesc('likes_count')->orderByDesc('comments_count');
    }

    public function withRelations():static
    {
        return $this->with([
            'user',
            'tags',
            'comments.user'
        ]);
    }

    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $total = null):static
    {
        return $this->published()->latest()->paginate($perPage);
    }
}
