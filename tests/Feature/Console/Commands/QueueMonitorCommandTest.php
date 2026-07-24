<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueMonitorCommandTest extends TestCase
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

    private function insertFailedJob(string $displayName, Carbon $failedAt, ?string $rawPayload = null): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => $rawPayload ?? json_encode(['displayName' => $displayName]),
            'exception' => 'Some exception trace',
            'failed_at' => $failedAt,
        ]);
    }

    public function test_it_reports_no_failed_jobs_when_none_exist(): void
    {
        $this->artisan('queue:monitor')
            ->expectsOutput('No failed jobs older than 24 hours')
            ->assertExitCode(0);
    }

    public function test_it_excludes_jobs_that_failed_more_than_24_hours_ago(): void
    {
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subDays(2));

        $this->artisan('queue:monitor')
            ->expectsOutput('No failed jobs older than 24 hours')
            ->assertExitCode(0);
    }

    public function test_it_includes_jobs_that_failed_within_the_last_24_hours(): void
    {
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subHours(2));

        $this->artisan('queue:monitor')
            ->doesntExpectOutput('No failed jobs older than 24 hours')
            ->assertExitCode(0);
    }

    public function test_it_includes_a_job_that_failed_exactly_at_the_24_hour_boundary(): void
    {
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subDay());

        $this->artisan('queue:monitor')
            ->doesntExpectOutput('No failed jobs older than 24 hours')
            ->assertExitCode(0);
    }

    public function test_it_groups_and_counts_failed_jobs_by_class(): void
    {
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subHours(1));
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subHours(2));
        $this->insertFailedJob('App\\Jobs\\CalculateTrendingArticlesJob', now()->subHours(3));

        $this->artisan('queue:monitor')
            ->expectsTable(
                ['job_class', 'failed_count'],
                [
                    ['Job' => 'App\\Jobs\\SendWeeklyDigestJob', 'Failed Count' => 2],
                    ['Job' => 'App\\Jobs\\CalculateTrendingArticlesJob', 'Failed Count' => 1],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_it_sorts_the_summary_table_by_failed_count_descending(): void
    {
        $this->insertFailedJob('App\\Jobs\\LowFrequencyJob', now()->subHours(1));
        $this->insertFailedJob('App\\Jobs\\HighFrequencyJob', now()->subHours(1));
        $this->insertFailedJob('App\\Jobs\\HighFrequencyJob', now()->subHours(2));
        $this->insertFailedJob('App\\Jobs\\HighFrequencyJob', now()->subHours(3));

        $this->artisan('queue:monitor')
            ->expectsTable(
                ['job_class', 'failed_count'],
                [
                    ['Job' => 'App\\Jobs\\HighFrequencyJob', 'Failed Count' => 3],
                    ['Job' => 'App\\Jobs\\LowFrequencyJob', 'Failed Count' => 1],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_it_defaults_to_unknown_when_display_name_is_missing_from_payload(): void
    {
        $this->insertFailedJob('irrelevant', now()->subHours(1), rawPayload: json_encode(['someOtherKey' => 'value']));

        $this->artisan('queue:monitor')
            ->expectsTable(
                ['job_class', 'failed_count'],
                [
                    ['Job' => 'Unknown', 'Failed Count' => 1],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_it_defaults_to_unknown_when_payload_is_malformed_json(): void
    {
        $this->insertFailedJob('irrelevant', now()->subHours(1), rawPayload: 'not-valid-json{{{');

        $this->artisan('queue:monitor')
            ->expectsTable(
                ['job_class', 'failed_count'],
                [
                    ['Job' => 'Unknown', 'Failed Count' => 1],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_command_exits_successfully_with_mixed_data(): void
    {
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subHours(1));
        $this->insertFailedJob('App\\Jobs\\SendWeeklyDigestJob', now()->subDays(3));

        $this->artisan('queue:monitor')->assertExitCode(0);
    }
}
