<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateTrendingArticlesJob;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CalculateTrendingArticlesJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_calculates_trending_articles_using_the_repository(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('calculateTrendingArticles')
            ->once()
            ->with(50);

        $job = new CalculateTrendingArticlesJob;

        $job->handle($repository);

        $this->assertTrue(true);
    }

    public function test_it_requests_exactly_fifty_trending_articles(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('calculateTrendingArticles')
            ->once()
            ->withArgs(function (int $limit) {
                return $limit === 50;
            });

        (new CalculateTrendingArticlesJob)->handle($repository);

        $this->assertTrue(true);
    }

    public function test_it_does_not_call_any_other_repository_method(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('calculateTrendingArticles')
            ->once()
            ->with(50);

        (new CalculateTrendingArticlesJob)->handle($repository);

        $this->assertTrue(true);
    }

    public function test_job_can_be_dispatched_onto_the_queue(): void
    {
        Queue::fake();

        CalculateTrendingArticlesJob::dispatch();

        Queue::assertPushed(CalculateTrendingArticlesJob::class);
    }

    public function test_job_resolves_repository_from_the_container_when_dispatched_synchronously(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('calculateTrendingArticles')
            ->once()
            ->with(50);

        $this->app->instance(ArticleRepositoryInterface::class, $repository);

        CalculateTrendingArticlesJob::dispatchSync();

        $this->assertTrue(true);
    }
}
