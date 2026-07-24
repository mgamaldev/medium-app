<?php

namespace Tests\Feature\Jobs;

use App\Enums\ArticleStatus;
use App\Jobs\SendWeeklyDigestJob;
use App\Mail\WeeklyDigestMail;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendWeeklyDigestJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-24 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function publishedArticle(array $overrides = []): Article
    {
        return Article::factory()->create(array_merge([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subDays(2),
        ], $overrides));
    }

    /**
     * Create a published article with a specific number of real Like and
     * Comment records attached, since likes_count/comments_count are not
     * real database columns — they only exist as computed values via
     * withCount(['likes', 'comments']) in the query builder.
     */
    private function articleWithEngagement(int $likes, int $comments, array $overrides = []): Article
    {
        $article = $this->publishedArticle($overrides);

        if ($likes > 0) {
            Like::factory()->count($likes)->create(['article_id' => $article->id]);
        }

        if ($comments > 0) {
            Comment::factory()->count($comments)->create(['article_id' => $article->id]);
        }

        return $article;
    }

    public function test_it_sends_the_weekly_digest_mail_to_the_subscribers_email(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create(['email' => 'subscriber@example.com']);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) {
            return $mail->hasTo('subscriber@example.com');
        });
    }

    public function test_it_only_includes_published_articles(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        $published = $this->publishedArticle();
        $draft = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'published_at' => null,
        ]);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($published, $draft) {
            $ids = $mail->articles->pluck('id');

            return $ids->contains($published->id) && ! $ids->contains($draft->id);
        });
    }

    public function test_it_excludes_articles_published_more_than_7_days_ago(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        $recent = $this->publishedArticle(['published_at' => now()->subDays(3)]);
        $old = $this->publishedArticle(['published_at' => now()->subDays(10)]);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($recent, $old) {
            $ids = $mail->articles->pluck('id');

            return $ids->contains($recent->id) && ! $ids->contains($old->id);
        });
    }

    public function test_it_includes_an_article_published_exactly_7_days_ago(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        $boundaryArticle = $this->publishedArticle(['published_at' => now()->subDays(7)]);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($boundaryArticle) {
            return $mail->articles->pluck('id')->contains($boundaryArticle->id);
        });
    }

    public function test_it_limits_the_digest_to_five_articles(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        Article::factory()->count(8)->create([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now()->subDays(1),
        ]);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) {
            return $mail->articles->count() === 5;
        });
    }

    public function test_it_orders_articles_by_likes_and_comments_count_descending(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        $lowEngagement = $this->articleWithEngagement(likes: 1, comments: 1);
        $highEngagement = $this->articleWithEngagement(likes: 20, comments: 30);
        $midEngagement = $this->articleWithEngagement(likes: 5, comments: 5);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($lowEngagement, $highEngagement, $midEngagement) {
            $orderedIds = $mail->articles->pluck('id')->values()->all();

            return $orderedIds === [$highEngagement->id, $midEngagement->id, $lowEngagement->id];
        });
    }

    public function test_it_sends_an_empty_digest_when_no_articles_qualify(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'published_at' => null,
        ]);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) {
            return $mail->articles->count() === 0;
        });
    }

    public function test_it_only_counts_likes_and_comments_from_the_correct_article(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create();

        // An article with heavy engagement on a DIFFERENT article should
        // not affect this article's own likes_count/comments_count.
        $targetArticle = $this->articleWithEngagement(likes: 2, comments: 2);
        $otherArticle = $this->articleWithEngagement(likes: 50, comments: 50);

        (new SendWeeklyDigestJob($subscriber))->handle();

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($otherArticle) {
            // otherArticle should rank first due to higher engagement,
            // proving counts are correctly scoped per-article.
            return $mail->articles->pluck('id')->first() === $otherArticle->id;
        });
    }

    public function test_job_can_be_dispatched_onto_the_queue(): void
    {
        Queue::fake();

        $subscriber = User::factory()->create();

        SendWeeklyDigestJob::dispatch($subscriber);

        Queue::assertPushed(SendWeeklyDigestJob::class, function (SendWeeklyDigestJob $job) use ($subscriber) {
            return $job->subscriber->is($subscriber);
        });
    }
}
