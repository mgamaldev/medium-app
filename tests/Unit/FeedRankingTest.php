<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_contains_published_articles_from_followed_users_ordered_by_date(): void
    {
        $user = User::factory()->create();
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        $user->follow($author1);
        $user->follow($author2);

        $oldArticle = Article::factory()->create([
            'user_id' => $author1->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subDays(2),
        ]);

        $newArticle = Article::factory()->create([
            'user_id' => $author2->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feed = $user->feed();

        $this->assertCount(2, $feed);

        $this->assertEquals($newArticle->id, $feed->first()->id);
        $this->assertEquals($oldArticle->id, $feed->last()->id);

    }

    public function test_feed_does_not_contain_draft_articles(): void {}

    public function test_feed_does_not_contain_articles_from_unfollowed_users(): void {}
}
