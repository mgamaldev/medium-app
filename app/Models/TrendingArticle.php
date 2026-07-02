<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Article;

class TrendingArticle extends Model
{
    protected $fillable = [
        'article_id',
        'trending_score',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
