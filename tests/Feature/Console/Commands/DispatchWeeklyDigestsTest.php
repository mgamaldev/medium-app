<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\SendWeeklyDigestJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DispatchWeeklyDigestsTest extends TestCase
{
    use RefreshDatabase;

    private string $currentWeek;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->currentWeek = now()->format('Y-\WW');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_dispatches_digest_job_for_subscribed_users(): void
    {
        Bus::fake();

        $user = User::factory()->create(['subscribed_to_digests' => true]);

        $this->artisan('digests:dispatch');

        Bus::assertDispatched(SendWeeklyDigestJob::class, function ($job) use ($user) {
            return $job->subscriber->is($user);
        });
    }

    public function test_it_dispatches_the_job_onto_the_digests_queue(): void
    {
        Bus::fake();

        User::factory()->create(['subscribed_to_digests' => true]);

        $this->artisan('digests:dispatch');

        Bus::assertDispatched(SendWeeklyDigestJob::class, function ($job) {
            return $job->queue === 'digests';
        });
    }

    public function test_it_does_not_dispatch_for_users_not_subscribed_to_digests(): void
    {
        Bus::fake();

        User::factory()->create(['subscribed_to_digests' => false]);

        $this->artisan('digests:dispatch');

        Bus::assertNotDispatched(SendWeeklyDigestJob::class);
    }

    public function test_it_records_a_digest_send_for_each_processed_user(): void
    {
        Bus::fake();

        $user = User::factory()->create(['subscribed_to_digests' => true]);

        $this->artisan('digests:dispatch');

        $this->assertDatabaseHas('digest_sends', [
            'user_id' => $user->id,
            'week_of' => $this->currentWeek,
        ]);
    }

    public function test_it_skips_users_who_already_received_a_digest_this_week(): void
    {
        Bus::fake();

        $user = User::factory()->create(['subscribed_to_digests' => true]);
        $user->recordDigestSend($this->currentWeek);

        $this->artisan('digests:dispatch');

        Bus::assertNotDispatched(SendWeeklyDigestJob::class);

        $this->assertEquals(
            1,
            DB::table('digest_sends')
                ->where('user_id', $user->id)
                ->where('week_of', $this->currentWeek)
                ->count()
        );
    }

    public function test_it_dispatches_again_for_a_new_week_even_if_already_sent_previously(): void
    {
        Bus::fake();

        $user = User::factory()->create(['subscribed_to_digests' => true]);
        $user->recordDigestSend('2026-W28');

        $this->artisan('digests:dispatch');

        Bus::assertDispatched(SendWeeklyDigestJob::class, function ($job) use ($user) {
            return $job->subscriber->is($user);
        });

        $this->assertDatabaseHas('digest_sends', [
            'user_id' => $user->id,
            'week_of' => $this->currentWeek,
        ]);
    }

    public function test_it_processes_multiple_subscribed_users(): void
    {
        Bus::fake();

        $subscribedUsers = User::factory()->count(3)->create(['subscribed_to_digests' => true]);
        User::factory()->count(2)->create(['subscribed_to_digests' => false]);

        $this->artisan('digests:dispatch');

        Bus::assertDispatchedTimes(SendWeeklyDigestJob::class, 3);

        foreach ($subscribedUsers as $user) {
            $this->assertDatabaseHas('digest_sends', [
                'user_id' => $user->id,
                'week_of' => $this->currentWeek,
            ]);
        }
    }

    public function test_it_handles_a_mix_of_new_and_already_sent_subscribers_in_the_same_run(): void
    {
        Bus::fake();

        $alreadySent = User::factory()->create(['subscribed_to_digests' => true]);
        $alreadySent->recordDigestSend($this->currentWeek);

        $pending = User::factory()->create(['subscribed_to_digests' => true]);

        $this->artisan('digests:dispatch');

        Bus::assertDispatchedTimes(SendWeeklyDigestJob::class, 1);

        Bus::assertDispatched(SendWeeklyDigestJob::class, function ($job) use ($pending) {
            return $job->subscriber->is($pending);
        });
    }

    public function test_it_does_nothing_when_there_are_no_subscribed_users(): void
    {
        Bus::fake();

        User::factory()->count(3)->create(['subscribed_to_digests' => false]);

        $this->artisan('digests:dispatch');

        Bus::assertNothingDispatched();

        $this->assertDatabaseCount('digest_sends', 0);
    }

    public function test_command_exits_successfully(): void
    {
        Bus::fake();

        User::factory()->create(['subscribed_to_digests' => true]);

        $this->artisan('digests:dispatch')->assertExitCode(0);
    }
}
