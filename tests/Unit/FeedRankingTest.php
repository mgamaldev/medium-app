<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class FeedRankingTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    // public function test_feed_does_not_contain_draft_articles(): void
    // {
    //     $user = User::factory()->create();
    //     $author = User::factory()->create();

    //     $user->follow($author);

    //     $article1 = Article::factory()->create([
    //         'user_id' => $author->id,
    //         'status' => ArticleStatus::PUBLISHED,
    //     ]);

    //     $article2 = Article::factory()->create([
    //         'user_id' => $author->id,
    //         'status' => ArticleStatus::DRAFT,
    //     ]);

    //     $feed = $user->feed();

    //     $this->assertCount(1, $feed);

    //     $this->assertEquals($article1->id, $feed->first()->id);
    // }

    // public function test_feed_does_not_contain_articles_from_unfollowed_users(): void
    // {
    //     $user = User::factory()->create();
    //     $author = User::factory()->create();

    //     $article = Article::factory()->create([
    //         'user_id' => $author->id,
    //         'status' => ArticleStatus::PUBLISHED,
    //     ]);

    //     $feed = $user->feed();

    //     $this->assertCount(0, $feed);

    // }
}
