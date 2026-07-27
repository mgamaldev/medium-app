<?php

namespace Tests\Unit\Notifications;

use App\Models\Article;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticlePublishedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_sent_via_mail_channel(): void
    {
        $article = Article::factory()->create();
        $notification = new ArticlePublishedNotification($article);

        $channels = $notification->via(new \stdClass);

        $this->assertEquals(['mail'], $channels);
    }

    #[Test]
    public function to_mail_returns_correct_message_content(): void
    {
        $article = Article::factory()->create(['title' => 'My Great Article']);
        $notification = new ArticlePublishedNotification($article);

        $mailMessage = $notification->toMail(new \stdClass);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertStringContainsString('My Great Article', $mailMessage->render());
    }

    #[Test]
    public function to_array_returns_empty_array(): void
    {
        $article = Article::factory()->create();
        $notification = new ArticlePublishedNotification($article);

        $result = $notification->toArray(new \stdClass);

        $this->assertEquals([], $result);
    }

    #[Test]
    public function failed_logs_error_with_expected_context(): void
    {
        $article = Article::factory()->create();
        $notification = new ArticlePublishedNotification($article);
        $exception = new \Exception('SMTP connection timed out');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'Article published notification failed permanently',
                \Mockery::on(function ($context) use ($article, $exception) {
                    return $context['job'] === ArticlePublishedNotification::class
                        && $context['user_id'] === $article->user_id
                        && $context['exception'] === $exception->getMessage()
                        && isset($context['trace']);
                })
            );

        $notification->failed($exception);
    }
}
