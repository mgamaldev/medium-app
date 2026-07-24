<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_follow_another_user(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        $response = $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $userToFollow->id,
            ]);

        $this->assertDatabaseHas('user_follower', [
            'follower_id' => $follower->id,
            'user_id' => $userToFollow->id,
        ]);

        $this->assertTrue(
            $follower->following()->where('user_id', $userToFollow->id)->exists()
        );
    }

    public function test_following_sends_notification_to_followed_user(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        Notification::assertSentTo(
            $userToFollow,
            NewFollowerNotification::class,
            function ($notification) use ($userToFollow, $follower) {
                return $notification->follower->id === $follower->id
                    && $notification->user->id === $userToFollow->id;
            }
        );
    }

    public function test_notification_is_not_sent_to_the_follower(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        Notification::assertNotSentTo($follower, NewFollowerNotification::class);
    }

    public function test_guest_cannot_follow_a_user(): void
    {
        $userToFollow = User::factory()->create();

        $response = $this->postJson("/api/users/{$userToFollow->id}/follow");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $follower = User::factory()->create();

        $response = $this->actingAs($follower)
            ->postJson('/api/users/99999/follow');

        $response->assertStatus(404);
    }

    public function test_response_returns_the_followed_user(): void
    {
        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        $response = $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $userToFollow->id,
                'username' => $userToFollow->username,
            ]);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/users/{$user->id}/follow");

        $response->assertStatus(422);

        $this->assertDatabaseMissing('user_follower', [
            'follower_id' => $user->id,
            'user_id' => $user->id,
        ]);

        Notification::assertNothingSent();
    }

    public function test_following_the_same_user_twice_does_not_create_duplicate_rows(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        $this->actingAs($follower)
            ->postJson("/api/users/{$userToFollow->id}/follow");

        $this->assertEquals(
            1,
            $follower->following()->where('user_id', $userToFollow->id)->count()
        );
    }
}
