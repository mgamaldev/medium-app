<?php

namespace Tests\Unit;

<<<<<<< HEAD
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
=======
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Models\User;
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)

class UserFollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_another_user(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();
        $follower->follow($user);

        $this->assertDatabaseHas('user_follower', [
            'user_id' => $user->id,
            'follower_id' => $follower->id,
        ]);
    }
<<<<<<< HEAD

=======
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage('You cannot follow yourself');

        $user->follow($user);

    }
<<<<<<< HEAD

=======
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    public function test_following_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();
        $follower->follow($user);

        $this->assertDatabaseCount('user_follower', 1);
    }
<<<<<<< HEAD

=======
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    public function test_user_can_unfollow_a_followed_user(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();
        $follower->follow($user);

        $follower->unfollow($user);

        $this->assertDatabaseCount('user_follower', 0);
    }
<<<<<<< HEAD

=======
>>>>>>> 74b904f ( add userFollowTest and ArticlePublishingTest + Edit Article and User models)
    public function test_unfollow_when_not_following(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();
        $follower->unfollow($user);

        $this->assertDatabaseCount('user_follower', 0);
    }
}
