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
use InvalidArgumentException;

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

    public function publish(): void
    {

        if (empty($this->body)) {
<<<<<<< HEAD
            throw new \Exception('Body is required');
        }

        if ($this->status === ArticleStatus::PUBLISHED) {
            throw new \Exception('Article is already published');
=======
            throw new InvalidArgumentException('Body is required');
        }

        if ($this->status === ArticleStatus::PUBLISHED) {
            throw new InvalidArgumentException('Article is already published');
>>>>>>> d1e2bb5de977880b6d321cacedc8dfc91b2c5491
        }

        $this->update([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

<<<<<<< HEAD
=======
        ArticlePublished::dispatch($this);

>>>>>>> d1e2bb5de977880b6d321cacedc8dfc91b2c5491
    }
}
