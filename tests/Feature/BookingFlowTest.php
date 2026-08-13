<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingCreated;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_complete_end_to_end_booking_payment_flow(): void
    {
        Event::fake([BookingCreated::class]);

        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $customer->createAsStripeCustomer(); // Necessary for successful charge via Cashier fake

        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'price' => 25.00,
        ]);

        // 1. Create a booking reservation
        $createResponse = $this->actingAs($user)
            ->postJson('/api/bookings', ['slot_id' => $slot->id], [
                'Idempotency-Key' => 'e2e-idempotency-key',
            ]);

        $createResponse->assertStatus(201);
        $bookingId = $createResponse->json('data.id');

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => BookingStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::PENDING->value,
        ]);

        // 2. Confirm booking with payment
        $confirmResponse = $this->actingAs($user)
            ->patchJson("/api/bookings/{$bookingId}/confirm", [
                'payment_method_id' => 'pm_card_visa',
            ]);

        $confirmResponse->assertStatus(200);

        // 3. Assert Real Outcomes
        // Verify DB slot state changed to BOOKED
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::BOOKED->value,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => BookingStatus::CONFIRMED->value,
        ]);

        // Verify Async Trigger (Event)
        Event::assertDispatched(BookingCreated::class, function ($event) use ($bookingId) {
            return $event->booking->id === $bookingId;
        });
    }
}
