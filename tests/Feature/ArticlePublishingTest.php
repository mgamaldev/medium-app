<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArticlePublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_article_with_valid_data_can_be_published(): void
    {
        $article = Article::factory()->create([
            'body' => 'This is a test article',
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->publish();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED,
        ]);
    }

    public function test_article_cannot_be_published_with_empty_body(): void
    {
        $article = Article::factory()->create([
            'body' => '',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Body is required');

        $article->publish();
    }

    public function test_article_cannot_be_published_if_already_published(): void
    {
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Article is already published');

        $article->publish();
    }

    public function test_publishing_an_article_dispatches_published_event(): void
    {
        Event::fake();

        $article = Article::factory()->create([
            'body' => 'This is a test article',
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->publish();

        Event::assertDispatched(ArticlePublished::class, fn ($e) => $e->article->is($article));
    }
}
