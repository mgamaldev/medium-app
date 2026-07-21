<?php

namespace Tests\Feature;

use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_booking_for_an_available_slot_using_database_seeder(): void
    {
        $this->seed();
        $slot = Slot::available()->first();
        $customer = Customer::first();

        $this->assertNotNull($slot, 'Seeder should generate at least one available slot.');
        $this->assertNotNull($customer, 'Seeder should generate at least one customer.');

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
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
        $customer = Customer::factory()->create();
        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Booking created successfully')
            ->assertJsonPath('data.slot.id', $slot->id)
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertNotEquals(SlotStatus::AVAILABLE, $slot->fresh()->status);
    }

    public function test_it_returns_a_client_error_when_slot_id_is_missing(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/bookings', [
            'customer_id' => $customer->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_a_client_error_when_customer_id_is_missing(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_a_client_error_for_a_nonexistent_slot(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/bookings', [
            'slot_id' => 999999,
            'customer_id' => $customer->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_a_client_error_for_a_nonexistent_customer(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => 999999,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_booking_a_slot_that_is_not_available(): void
    {
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();

        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        // First booking.
        $first = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customerA->id,
        ]);
        $first->assertStatus(201);

        // Second booking.
        $second = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customerB->id,
        ]);

        $second->assertStatus(422);

        $this->assertEquals(
            1,
            Booking::where('slot_id', $slot->id)->count(),
            'Only one booking should exist for a slot that only allows a single confirmed booking.'
        );
    }

    public function test_it_response_shape_includes_loaded_slot_and_customer(): void
    {
        $customer = Customer::factory()->create();
        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
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
