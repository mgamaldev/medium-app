<?php

namespace Tests\Unit\Notifications;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentReceivedNotification;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CommentReceivedNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private function makeComment(?string $username = null, ?string $body = null, ?string $articleTitle = null): Comment
    {
        $author = User::factory()->create($username ? ['username' => $username] : []);
        $article = Article::factory()->create($articleTitle ? ['title' => $articleTitle] : []);

        return Comment::factory()->create([
            'user_id' => $author->id,
            'article_id' => $article->id,
            'body' => $body ?? 'A test comment body',
        ]);
    }

    public function test_it_is_only_sent_via_the_mail_channel(): void
    {
        $comment = $this->makeComment();
        $notification = new CommentReceivedNotification($comment);

        $channels = $notification->via((object) []);

        $this->assertSame(['mail'], $channels);
    }

    public function test_it_routes_the_mail_channel_through_the_notifications_queue(): void
    {
        $comment = $this->makeComment();
        $notification = new CommentReceivedNotification($comment);

        $this->assertSame(['mail' => 'notifications'], $notification->viaQueues());
    }

    public function test_it_is_configured_to_retry_up_to_three_times_with_backoff(): void
    {
        $comment = $this->makeComment();
        $notification = new CommentReceivedNotification($comment);

        $this->assertSame(3, $notification->tries);
        $this->assertSame([10, 30, 60], $notification->backoff);
    }

    public function test_the_mail_message_includes_the_commenter_username_body_and_article_title(): void
    {
        $comment = $this->makeComment(
            username: 'jane_doe',
            body: 'Great points in this post!',
            articleTitle: 'Understanding Laravel Queues'
        );

        $notification = new CommentReceivedNotification($comment);

        $mailMessage = $notification->toMail((object) []);

        $this->assertStringContainsString('jane_doe', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Great points in this post!', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Understanding Laravel Queues', $mailMessage->introLines[0]);
    }

    public function test_notification_is_sent_to_the_correct_recipient(): void
    {
        Notification::fake();

        $recipient = User::factory()->create();
        $comment = $this->makeComment();

        $recipient->notify(new CommentReceivedNotification($comment));

        Notification::assertSentTo(
            $recipient,
            CommentReceivedNotification::class,
            function (CommentReceivedNotification $notification) use ($comment) {
                return $notification->comment->is($comment);
            }
        );
    }

    public function test_to_array_returns_an_empty_array(): void
    {
        $comment = $this->makeComment();
        $notification = new CommentReceivedNotification($comment);

        $this->assertSame([], $notification->toArray((object) []));
    }

    public function test_failed_logs_an_error_with_the_comments_user_id(): void
    {
        $comment = $this->makeComment();
        $notification = new CommentReceivedNotification($comment);
        $exception = new Exception('Mail server unreachable');

        Log::shouldReceive('error')
            ->once()
            ->with('Comment received notification failed permanently', Mockery::on(function (array $context) use ($comment, $exception) {
                return $context['job'] === CommentReceivedNotification::class
                    && $context['user_id'] === $comment->user_id
                    && $context['exception'] === $exception->getMessage()
                    && is_string($context['trace']);
            }));

        $notification->failed($exception);
    }
}
