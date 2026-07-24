<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PruneStaleDraftsJob;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class PruneStaleDraftsJobTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function test_it_calls_repository_with_the_correct_stale_date_and_chunk_size(): void
    {
        $expectedStaleDate = now()->subDays(90);

        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('pruneStaleDrafts')
            ->once()
            ->withArgs(function ($staleDate, $chunkSize, $callback) use ($expectedStaleDate) {
                return $staleDate->equalTo($expectedStaleDate)
                    && $chunkSize === 1000
                    && is_callable($callback);
            });

        Log::spy();

        (new PruneStaleDraftsJob)->handle($repository);
    }

    public function test_it_accumulates_the_pruned_count_across_multiple_chunks(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('pruneStaleDrafts')
            ->once()
            ->withArgs(function ($staleDate, $chunkSize, $callback) {
                $callback(400);
                $callback(350);
                $callback(120);

                return true;
            });

        Log::spy();

        (new PruneStaleDraftsJob)->handle($repository);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Stale drafts cleaned up successfully!', [
                'total_pruned_articles' => 870,
                'retention_period_days' => 90,
            ]);
    }

    public function test_it_logs_zero_pruned_articles_when_no_drafts_are_stale(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('pruneStaleDrafts')
            ->once()
            ->withArgs(function ($staleDate, $chunkSize, $callback) {
                return true;
            });

        Log::spy();

        (new PruneStaleDraftsJob)->handle($repository);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Stale drafts cleaned up successfully!', [
                'total_pruned_articles' => 0,
                'retention_period_days' => 90,
            ]);
    }

    public function test_it_logs_the_correct_retention_period(): void
    {
        $repository = Mockery::mock(ArticleRepositoryInterface::class);

        $repository->shouldReceive('pruneStaleDrafts')
            ->once()
            ->withArgs(function ($staleDate, $chunkSize, $callback) {
                $callback(5);

                return true;
            });

        Log::spy();

        (new PruneStaleDraftsJob)->handle($repository);

        Log::shouldHaveReceived('info')
            ->once()
            ->with(Mockery::any(), Mockery::on(function (array $context) {
                return $context['retention_period_days'] === 90;
            }));
    }

    public function test_job_can_be_dispatched_onto_the_queue(): void
    {
        Queue::fake();

        PruneStaleDraftsJob::dispatch();

        Queue::assertPushed(PruneStaleDraftsJob::class);
    }
}
