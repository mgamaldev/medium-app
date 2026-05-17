<?php

namespace App\Strategies\Contracts;

use App\Models\Article;
use App\Models\User;

interface ContentVisibilityStrategy
{
    public function canView(?User $user, Article $article): bool;
}
