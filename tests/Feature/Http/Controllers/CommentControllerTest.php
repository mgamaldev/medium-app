<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Notifications\CommentReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_comment_to_article(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $response = $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => 'This is a great article!',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a great article!',
            'user_id' => $commenter->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_comment_creation_notifies_article_owner(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => 'Nice work!',
            ]);

        Notification::assertSentTo(
            $author,
            CommentReceivedNotification::class,
            function ($notification, $channels) use ($article) {
                return $notification->comment->body === 'Nice work!'
                    && $notification->comment->article_id === $article->id;
            }
        );
    }

    public function test_notification_is_not_sent_to_commenter_if_they_are_not_the_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => 'Some comment',
            ]);

        Notification::assertNotSentTo($commenter, CommentReceivedNotification::class);
    }

    public function test_comment_requires_body(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $response = $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_comment_body_must_be_a_string(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $response = $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => ['not', 'a', 'string'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_guest_cannot_add_comment(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $response = $this->postJson("/api/articles/{$article->id}/comments", [
            'body' => 'Trying to comment without auth',
        ]);

        $response->assertStatus(401);
    }

    public function test_response_returns_the_article(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($author)->create();

        $response = $this->actingAs($commenter)
            ->postJson("/api/articles/{$article->id}/comments", [
                'body' => 'A comment',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $article->id,
            ]);
    }

    public function test_returns_404_for_nonexistent_article(): void
    {
        $commenter = User::factory()->create();

        $response = $this->actingAs($commenter)
            ->postJson('/api/articles/99999/comments', [
                'body' => 'Comment on missing article',
            ]);

        $response->assertStatus(404);
    }
}
