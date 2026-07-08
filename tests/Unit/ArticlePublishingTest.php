<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_publishing_an_article_triggers_email_notification(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $follower->follow($author);

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'body' => 'This is a test article',
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->publish();

    }
}
