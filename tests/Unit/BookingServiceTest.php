<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = new BookingService;
    }

    public function test_it_creates_a_booking_when_slot_is_available(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        $booking = $this->bookingService->createBooking($slot, $customer);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($slot->id, $booking->slot_id);
        $this->assertEquals($customer->id, $booking->customer_id);
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    }

    public function test_updates_slot_status_to_booked_after_creating_booking(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        $this->bookingService->createBooking($slot, $customer);

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::BOOKED->value,
        ]);
    }

    public function test_throws_exception_when_slot_is_already_booked(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::BOOKED]);
        $customer = Customer::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot is not available');

        $this->bookingService->createBooking($slot, $customer);
    }

    public function test_throws_exception_when_slot_status_changed_after_fetch_but_before_lock(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        Slot::whereKey($slot->id)->update(['status' => SlotStatus::BOOKED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot is not available');

        $this->bookingService->createBooking($slot, $customer);
    }

    public function test_does_not_create_booking_when_slot_is_not_available(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::BOOKED]);
        $customer = Customer::factory()->create();

        try {
            $this->bookingService->createBooking($slot, $customer);
        } catch (\Exception $e) {
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_rolls_back_transaction_when_booking_creation_fails(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        $customer->delete();

        try {
            $this->bookingService->createBooking($slot, $customer);
        } catch (\Throwable $e) {
        }

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::AVAILABLE->value,
        ]);
    }

    public function test_throws_custom_exception_when_lock_times_out(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        $lockKey = "booking:slot:{$slot->id}";
        $externalLock = Cache::lock($lockKey, 30);
        $externalLock->get();

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Excuse for getting the lock, the crowd is very high');

            $this->bookingService->createBooking($slot, $customer);
        } finally {
            $externalLock->release();
        }
    }
}
