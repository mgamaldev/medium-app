<?php

namespace Tests\Feature;

use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithCustomer(): User
    {
        return User::factory()
            ->has(Customer::factory())
            ->create();
    }

    public function test_it_creates_a_booking_for_an_available_slot_using_database_seeder(): void
    {
        $this->seed();
        $slot = Slot::available()->first();
        $user = User::factory()->has(Customer::factory())->create();
        $customer = $user->customer;

        $this->assertNotNull($slot, 'Seeder should generate at least one available slot.');

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slot.id', $slot->id)
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals(SlotStatus::BOOKED, $slot->fresh()->status);
    }

    public function test_it_creates_a_booking_for_an_available_slot(): void
    {
        $user = $this->createUserWithCustomer();
        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Booking created successfully')
            ->assertJsonPath('data.slot.id', $slot->id)
            ->assertJsonPath('data.customer.id', $user->customer->id);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $user->customer->id,
        ]);

        $this->assertNotEquals(SlotStatus::AVAILABLE, $slot->fresh()->status);
    }

    public function test_it_returns_a_client_error_when_slot_id_is_missing(): void
    {
        $user = $this->createUserWithCustomer();

        $response = $this->actingAs($user)->postJson('/api/bookings', []);

        $response->assertStatus(422);
    }

    public function test_it_returns_a_client_error_for_a_nonexistent_slot(): void
    {
        $user = $this->createUserWithCustomer();

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => 999999,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_client_error_when_user_has_no_customer_profile(): void
    {
        $user = User::factory()->create();
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_it_rejects_booking_a_slot_that_is_not_available(): void
    {
        $userA = $this->createUserWithCustomer();
        $userB = $this->createUserWithCustomer();

        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $first = $this->actingAs($userA)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);
        $first->assertStatus(201);

        $second = $this->actingAs($userB)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $second->assertStatus(422);

        $this->assertEquals(
            1,
            Booking::where('slot_id', $slot->id)->count(),
            'Only one booking should exist for a slot that only allows a single confirmed booking.'
        );
    }

    public function test_it_ignores_customer_id_in_request_body_and_uses_authenticated_customer(): void
    {
        $user = $this->createUserWithCustomer();
        $otherCustomer = Customer::factory()->create();

        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer.id', $user->customer->id);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $user->customer->id,
        ]);

        $this->assertDatabaseMissing('bookings', [
            'customer_id' => $otherCustomer->id,
        ]);
    }

    public function test_it_response_shape_includes_loaded_slot_and_customer(): void
    {
        $user = $this->createUserWithCustomer();
        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'slot_id',
                    'customer_id',
                    'status',
                    'slot',
                    'customer',
                ],
            ]);
    }
}
