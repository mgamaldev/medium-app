<?php

namespace App\Models;

use App\Builders\ArticleQueryBuilder;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Events\ArticlePublished;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ArticleStatus $status
 * @property ArticleVisibility $visibility
 */
class Article extends Model
{
    use HasFactory, SoftDeletes;

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

    /**
     * @return BelongsTo<User, $this>
     */
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

    public function publish(): void
    {

        if (empty($this->body)) {
            throw new \Exception('Body is required');
        }

        if ($this->status === ArticleStatus::PUBLISHED) {
            throw new \Exception('Article is already published');
        }

        $this->update([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
