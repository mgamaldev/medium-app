<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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

    public function test_it_creates_a_pending_booking_when_slot_is_available(): void
    {
        config(['cache.default' => 'array']);

        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        $booking = $this->bookingService->createBooking($slot, $customer, $idempotencyKey);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($slot->id, $booking->slot_id);
        $this->assertEquals($customer->id, $booking->customer_id);
        $this->assertEquals(BookingStatus::PENDING, $booking->status);
        $this->assertEquals($idempotencyKey, $booking->idempotency_key);

        $this->assertDatabaseHas('bookings', [
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PENDING->value,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function test_reserves_slot_as_pending_after_creating_booking(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        $this->bookingService->createBooking($slot, $customer, $idempotencyKey);

        // The slot is reserved (moved out of AVAILABLE) immediately on
        // creation so a second customer can't grab it while payment is
        // still pending. It only becomes BOOKED once payment is confirmed.
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::PENDING->value,
        ]);
    }

    public function test_second_customer_cannot_book_a_slot_already_reserved_by_another(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $firstCustomer = Customer::factory()->create();
        $secondCustomer = Customer::factory()->create();

        $this->bookingService->createBooking($slot, $firstCustomer, (string) Str::uuid());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot is not available');

        $this->bookingService->createBooking($slot->fresh(), $secondCustomer, (string) Str::uuid());
    }

    public function test_throws_exception_when_slot_is_already_booked(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::BOOKED]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot is not available');

        $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
    }

    public function test_throws_exception_when_slot_status_changed_after_fetch_but_before_lock(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        Slot::whereKey($slot->id)->update(['status' => SlotStatus::BOOKED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot is not available');

        $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
    }

    public function test_does_not_create_booking_when_slot_is_not_available(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::BOOKED]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        try {
            $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
        } catch (\Exception $e) {
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_rolls_back_transaction_when_booking_creation_fails(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        $customer->delete();

        try {
            $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
        } catch (\Throwable $e) {
        }

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => SlotStatus::AVAILABLE->value,
        ]);
    }

    public function test_throws_custom_exception_when_lock_times_out(): void
    {
        config(['cache.default' => 'array']);
        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();

        $lockKey = "booking:slot:{$slot->id}";

        $externalLock = Cache::lock($lockKey, 30);
        $externalLock->get();

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('The slot is currently being booked by another user. Please try again.');
            $idempotencyKey = (string) Str::uuid();

            $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
        } finally {
            $externalLock->release();
        }
    }

    public function test_returns_existing_booking_on_repeated_request_without_dispatching_event(): void
    {
        Event::fake();

        $slot = Slot::factory()->create(['status' => SlotStatus::AVAILABLE]);
        $customer = Customer::factory()->create();
        $idempotencyKey = (string) Str::uuid();

        $firstBooking = $this->bookingService->createBooking($slot, $customer, $idempotencyKey);
        $secondBooking = $this->bookingService->createBooking($slot, $customer, $idempotencyKey);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals($firstBooking->id, $secondBooking->id);
        $this->assertEquals($firstBooking->idempotency_key, $secondBooking->idempotency_key);

        Event::assertNotDispatched(BookingCreated::class);
    }
}
