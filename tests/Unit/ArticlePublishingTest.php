<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
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

    public function test_publishing_an_article_triggers_email_notification(): void
    {
        Event::fake([ArticlePublished::class]);

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $follower->follow($author);

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'body' => 'This is a test article',
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->publish();

        Event::assertDispatched(ArticlePublished::class, function ($event) use ($article) {
            return $event->article->id === $article->id;
        });
    }

    public function test_article_published_event_has_correct_broadcast_channels(): void
    {
        $article = Article::factory()->create();

        $channels = (new ArticlePublished($article))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-channel-name', $channels[0]->name);

    }
}
