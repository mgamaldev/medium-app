<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedRankingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_returns_empty_feed_when_following_no_one(): void
    {
        Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feed = $this->user->feed();

        $this->assertCount(0, $feed);
    }

    public function test_it_only_includes_articles_from_followed_users(): void
    {
        $followedUser = User::factory()->create();
        $strangerUser = User::factory()->create();

        $this->user->follow($followedUser);

        $followedArticle = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        Article::factory()->create([
            'user_id' => $strangerUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feed = $this->user->feed();

        $this->assertCount(1, $feed);
        $this->assertTrue($feed->contains('id', $followedArticle->id));
    }

    public function test_it_excludes_non_published_articles_from_followed_users(): void
    {
        $followedUser = User::factory()->create();
        $this->user->follow($followedUser);

        $draftArticle = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::DRAFT,
            'published_at' => null,
        ]);

        $archivedArticle = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::ARCHIVED,
            'published_at' => now(),
        ]);

        $publishedArticle = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feed = $this->user->feed();

        $this->assertCount(1, $feed);
        $this->assertTrue($feed->contains('id', $publishedArticle->id));
        $this->assertFalse($feed->contains('id', $draftArticle->id));
        $this->assertFalse($feed->contains('id', $archivedArticle->id));
    }

    public function test_it_orders_articles_by_published_at_descending(): void
    {
        $followedUser = User::factory()->create();
        $this->user->follow($followedUser);

        $oldest = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subDays(3),
        ]);

        $newest = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $middle = Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $feed = $this->user->feed();

        $this->assertCount(3, $feed);
        $this->assertEquals($newest->id, $feed[0]->id);
        $this->assertEquals($middle->id, $feed[1]->id);
        $this->assertEquals($oldest->id, $feed[2]->id);
    }

    public function test_it_combines_articles_from_multiple_followed_users_in_order(): void
    {
        $followedA = User::factory()->create();
        $followedB = User::factory()->create();

        $this->user->follow($followedA);
        $this->user->follow($followedB);

        $articleA = Article::factory()->create([
            'user_id' => $followedA->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $articleB = Article::factory()->create([
            'user_id' => $followedB->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feed = $this->user->feed();

        $this->assertCount(2, $feed);
        $this->assertEquals($articleB->id, $feed[0]->id);
        $this->assertEquals($articleA->id, $feed[1]->id);
    }

    public function test_unfollowing_a_user_removes_their_articles_from_the_feed(): void
    {
        $followedUser = User::factory()->create();
        $this->user->follow($followedUser);

        Article::factory()->create([
            'user_id' => $followedUser->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $this->user->unfollow($followedUser);

        $feed = $this->user->feed();

        $this->assertCount(0, $feed);
    }
}
