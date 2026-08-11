<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_complete_end_to_end_auth_flow(): void
    {
        // 1. User Registration
        $registerPayload = [
            'username' => 'Alice E2E',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ];

        $registerResponse = $this->postJson('/api/register', $registerPayload);
        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['access_token', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'username' => 'Alice E2E',
        ]);

        // 2. User Login
        $loginPayload = [
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ];

        $loginResponse = $this->postJson('/api/login', $loginPayload);
        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user' => ['id', 'email']]);

        $token = $loginResponse->json('access_token');

        // 3. Accessing Protected Route
        $userResponse = $this->withToken($token)->getJson('/api/user');
        $userResponse->assertStatus(200)
            ->assertJsonFragment(['email' => 'alice@example.com']);
    }
}
