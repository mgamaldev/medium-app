<?php

namespace App\Models;

use App\Builders\ArticleQueryBuilder;
use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class Article extends Model
{
    use HasFactory;

    #[Override]
    public function newEloquentBuilder($query): ArticleQueryBuilder
    {
        return new ArticleQueryBuilder($query);
    }

    protected $fillable = ['user_id', 'title', 'body', 'status', 'published_at', 'is_featured', 'cover_image'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
        ];
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);

    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);

    }

    public function comments()
    {
        return $this->hasMany(Comment::class);

    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function readingLists()
    {
        return $this->belongsToMany(ReadingList::class);
    }
}
