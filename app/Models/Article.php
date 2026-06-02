<?php

namespace App\Models;

use App\Builders\ArticleQueryBuilder;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @property ArticleStatus $status
 * @property ArticleVisibility $visibility
 */
class Article extends Model
{
    use HasFactory;

    public function newEloquentBuilder($query): ArticleQueryBuilder
    {
        return new ArticleQueryBuilder($query);
    }

    protected $fillable = ['user_id', 'title', 'body', 'status', 'published_at', 'is_featured', 'cover_image'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'visibility' => ArticleVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);

    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);

    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);

    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function readingLists(): BelongsToMany
    {
        return $this->belongsToMany(ReadingList::class);
    }
}
