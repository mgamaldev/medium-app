<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_author_can_create_draft_article(): void
    {
        Storage::fake('s3');

        $fakeDiskPath = 'covers/cover.jpg';
        Storage::disk('s3')->put($fakeDiskPath, 'cover-image');

        $author = User::factory()->create();

        Sanctum::actingAs($author);

        $data = [
            'title' => 'Test Title',
            'body' => 'Test Body',
            'status' => ArticleStatus::DRAFT,
            'cover_image' => $fakeDiskPath,
        ];

        $response = $this->postJson('/api/articles', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Title',
            'user_id' => $author->id,
            'status' => ArticleStatus::DRAFT,
        ]);
    }

    #[Test]
    public function test_author_can_publish_their_article(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $author->followers()->attach($follower);

        Sanctum::actingAs($author);

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => ArticleStatus::DRAFT,
        ]);

        $response = $this->postJson("/api/articles/{$article->id}/publish");

        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'user_id' => $author->id,
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->assertNotNull($article->fresh()->published_at);

        Notification::assertSentTo($follower, ArticlePublishedNotification::class);
        Notification::assertNotSentTo($author, ArticlePublishedNotification::class);
    }

    #[Test]
    public function test_published_articles_appear_in_feed_and_drafts_do_not(): void
    {
        $reader = User::factory()->create();
        $author = User::factory()->create();

        $reader->follow($author);

        $draftArticle = Article::factory()->create([
            'user_id' => $author->id,
            'status' => ArticleStatus::DRAFT,
        ]);

        $publishedArticle = Article::factory()->create([
            'user_id' => $author->id,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $feedArticle = $reader->feed();

        $this->assertTrue($feedArticle->contains($publishedArticle));
        $this->assertFalse($feedArticle->contains($draftArticle));
    }

    #[Test]
    public function test_article_creation_requires_a_title(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author);

        $data = [
            'title' => '',
            'body' => 'Test Body',
        ];

        $response = $this->postJson('/api/articles', $data);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('articles', 0);
    }

    #[Test]
    public function test_user_cannot_publish_others_articles(): void
    {
        $author = User::factory()->create();
        $intruder = User::factory()->create();

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => ArticleStatus::DRAFT,
        ]);

        $this->actingAs($intruder);

        $response = $this->postJson("/api/articles/{$article->id}/publish");

        $response->assertStatus(403);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::DRAFT,
        ]);
    }

    #[Test]
    public function it_can_complete_end_to_end_article_publish_flow(): void
    {
        Notification::fake();

        // 1. Register User (Author)
        $registerPayload = [
            'username' => 'Author E2E',
            'email' => 'author@example.com',
            'password' => 'secret123',
        ];

        $registerResponse = $this->postJson('/api/register', $registerPayload);
        $registerResponse->assertStatus(201);
        $token = $registerResponse->json('access_token');

        $author = User::where('email', 'author@example.com')->first();
        $follower = User::factory()->create();
        $author->followers()->attach($follower); // Add follower manually for assertion

        // 2. Create Article
        $articlePayload = [
            'title' => 'E2E Article Title',
            'body' => 'E2E Article Body content goes here.',
            'status' => ArticleStatus::DRAFT->value,
            'cover_image' => 'covers/test.jpg',
        ];

        $createResponse = $this->withToken($token)->postJson('/api/articles', $articlePayload);
        $createResponse->assertStatus(201);

        $articleId = $createResponse->json('id');

        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'title' => 'E2E Article Title',
            'status' => ArticleStatus::DRAFT->value,
            'user_id' => $author->id,
        ]);

        // 3. Publish Article
        $publishResponse = $this->withToken($token)->postJson("/api/articles/{$articleId}/publish");
        $publishResponse->assertStatus(200);

        // Assert Real Outcomes
        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'status' => ArticleStatus::PUBLISHED->value,
        ]);

        Notification::assertSentTo($follower, ArticlePublishedNotification::class, function ($notification) use ($articleId) {
            return $notification->article->id === $articleId;
        });
    }
}
