<?php

namespace Tests\Feature;

use App\Jobs\SendWeeklyDigestJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigestSchedulerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_complete_end_to_end_digest_scheduler_flow(): void
    {
        Queue::fake();

        // Setup users: one subscribed, one not subscribed
        $subscribedUser = User::factory()->create([
            'subscribed_to_digests' => true,
        ]);

        $unsubscribedUser = User::factory()->create([
            'subscribed_to_digests' => false,
        ]);

        // 1. Run the Digest Dispatch Command
        $exitCode = Artisan::call('digests:dispatch');
        $this->assertEquals(0, $exitCode);

        // 2. Assert Real Outcomes
        // Verify Async Trigger dispatched to specific queue
        Queue::assertPushedOn('digests', SendWeeklyDigestJob::class, function ($job) use ($subscribedUser) {
            return $job->subscriber->id === $subscribedUser->id;
        });

        Queue::assertNotPushed(SendWeeklyDigestJob::class, function ($job) use ($unsubscribedUser) {
            return $job->subscriber->id === $unsubscribedUser->id;
        });

        // Verify Database log that it was sent
        $currentWeek = now()->format('Y-\WW');
        $this->assertDatabaseHas('digest_sends', [
            'user_id' => $subscribedUser->id,
            'week_of' => $currentWeek,
        ]);

        $this->assertDatabaseMissing('digest_sends', [
            'user_id' => $unsubscribedUser->id,
            'week_of' => $currentWeek,
        ]);
    }
}
