<?php

namespace Tests\Unit;

<<<<<<< HEAD
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
=======
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Models\Article;
use App\Models\User;
use App\Enums\ArticleStatus;
use Illuminate\Mail\Mailable;
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)

class ArticlePublishingTest extends TestCase
{
    use RefreshDatabase;

<<<<<<< HEAD
    public function test_draft_article_with_valid_data_can_be_published(): void
=======
    public function test_draft_article_with_valid_data_can_be_published():void
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
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
<<<<<<< HEAD

    public function test_article_cannot_be_published_with_empty_body(): void
=======
    public function test_article_cannot_be_published_with_empty_body():void
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    {
        $article = Article::factory()->create([
            'body' => '',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Body is required');

        $article->publish();
    }
<<<<<<< HEAD

    public function test_article_cannot_be_published_if_already_published(): void
=======
    public function test_article_cannot_be_published_if_already_published():void
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    {
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Article is already published');

        $article->publish();
    }

<<<<<<< HEAD
    public function test_publishing_an_article_triggers_email_notification(): void
=======
    public function test_publishing_an_article_triggers_email_notification():void
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    {
        Mail::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();
<<<<<<< HEAD

=======
    
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
        $follower->follow($author);

        $article = Article::factory()->create([
            'user_id' => $author->id,
            'body' => 'This is a test article',
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->publish();

<<<<<<< HEAD
=======
        Mail::assertSent(function (Mailable $mailable) use ($follower) {
            return $mailable->hasTo($follower->email);
        });
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    }
}
