<?php

namespace App\Models;

use App\Builders\ArticleQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\Like;
use App\Models\ReadingList;
use App\Enums\ArticleStatus;
use Override;

class Article extends Model
{
    use HasFactory;

    #[Override]
    public function newEloquentBuilder($query): ArticleQueryBuilder
    {
        return new ArticleQueryBuilder($query);
    }

    protected $fillable = ['title','body','status'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
        ];
    }

    public function user()
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

    public function scopePublished($query)
    {
        return $query->where('status', ArticleStatus::PUBLISHED);
    }

    public function scopeTrending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    
}
