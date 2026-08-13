<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FollowFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_complete_end_to_end_follow_flow(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        // 1. Follow Author
        $response = $this->actingAs($follower)->postJson("/api/users/{$author->id}/follow");
        $response->assertStatus(200);

        // 2. Assert Real Outcomes
        // Verify Database Relation
        $this->assertDatabaseHas('user_follower', [
            'follower_id' => $follower->id,
            'user_id' => $author->id,
        ]);

        // Verify Async Trigger
        Notification::assertSentTo($author, NewFollowerNotification::class, function ($notification) use ($follower) {
            return $notification->follower->id === $follower->id;
        });
    }
}
