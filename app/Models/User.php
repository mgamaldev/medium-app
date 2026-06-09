<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read int|null $followers_count
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => 'boolean',
        ];
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_follower', 'follower_id', 'user_id');
    }

    public function follow(User $userToFollow)
    {
        if ($this->id === $userToFollow->id) {
            throw new \Exception('You cannot follow yourself');
        }

        $this->following()->syncWithoutDetaching($userToFollow->id);

        return $this;
    }

    public function unfollow(User $userToUnfollow)
    {
        $this->following()->detach($userToUnfollow->id);

        return $this;
    }

    public function feed()
    {
        $followedUserId = $this->following()->pluck('user_id');

        return Article::query()->whereIn('user_id', $followedUserId)
            ->where('status', ArticleStatus::PUBLISHED)
            ->orderBy('published_at', 'desc')
            ->get();

    }
}
