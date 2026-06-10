<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ArticlePublishedNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_author_can_create_draft_article(): void
    {  

        $author = User::factory()->create();

        $this->actingAs($author);

        $data = [
            'title' => 'Test Title',
            'body' => 'Test Body',
            'status' => ArticleStatus::DRAFT,
        ];

        $response = $this->postJson('/api/articles', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Title',
            'user_id' => $author->id,
            'status' => 'draft',
        ]);
    }

    public function test_author_can_publish_their_article(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $this->actingAs($author);

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => ArticleStatus::DRAFT,
        ]);

        $response = $this->postJson("/api/articles/{$article->id}/publish");

        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $author->id,
            'status' => ArticleStatus::PUBLISHED,
        ]);

        Notification::assertSentTo($follower, ArticlePublishedNotification::class);
        Notification::assertNotSentTo($author, ArticlePublishedNotification::class);

    }
    public function test_published_articles_appear_in_feed_and_drafts_do_not(): void
    {

    }
    public function test_article_creation_requires_a_title(): void
    {
        
    }
    public function test_user_cannot_publish_others_articles(): void
    {
        
    }


}
