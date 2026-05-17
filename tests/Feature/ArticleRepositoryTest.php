<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\User;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ArticleRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ArticleRepositoryInterface::class);
    }

    #[Test]
    public function can_create_an_article()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $data = [
            'title' => 'Test Article',
            'body' => 'This is a test body',
            'status' => ArticleStatus::PUBLISHED,
            'user_id' => $user->id,
        ];

        $article = $this->repository->create($data);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->title);
        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function returns_only_published_articles()
    {

        Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);
        Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        $results = $this->repository->getPublished();

        $this->assertCount(1, $results);
        $this->assertEquals(ArticleStatus::PUBLISHED, $results->first()->status);
    }

    public function dispatches_article_published_event()
    {
        Event::fake();

        $user = User::factory()->create();

        $data = [
            'title' => 'Test Article',
            'body' => 'This is a test body',
            'status' => ArticleStatus::PUBLISHED,
            'user_id' => $user->id,
        ];

        $this->repository->create($data);

        Event::assertDispatched(ArticlePublished::class);

    }

    public function not_dispatch_fails_event()
    {
        Event::fake();

        $user = User::factory()->create();

        $data = [
            'title' => 'Test Article',
            'body' => 'This is a test body',
            'status' => ArticleStatus::DRAFT,
            'user_id' => $user->id,
        ];

        DB::beginTransaction();

        try {
            $this->repository->create($data);

            throw new \Exception('Database crash');
        } catch (\Exception $e) {
            DB::rollBack();
        }

        Event::assertNotDispatched(ArticlePublished::class);
    }
}
