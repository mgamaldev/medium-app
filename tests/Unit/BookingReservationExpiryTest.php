<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingReservationExpiryTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->bookingService = new BookingService;
    }

    public function test_releases_a_stale_pending_reservation_back_to_available(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::PENDING]);
        $customer = Customer::factory()->create();

        $booking = Booking::create([
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PENDING,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $booking->forceFill(['created_at' => now()->subMinutes(20)])->save();

        $released = $this->bookingService->releaseExpiredReservations(15);

        $this->assertEquals(1, $released);
        $this->assertEquals(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertEquals(SlotStatus::AVAILABLE, $slot->fresh()->status);
    }

    public function test_does_not_release_a_reservation_still_within_the_ttl_window(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::PENDING]);
        $customer = Customer::factory()->create();

        $booking = Booking::create([
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PENDING,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $booking->forceFill(['created_at' => now()->subMinutes(5)])->save();

        $released = $this->bookingService->releaseExpiredReservations(15);

        $this->assertEquals(0, $released);
        $this->assertEquals(BookingStatus::PENDING, $booking->fresh()->status);
        $this->assertEquals(SlotStatus::PENDING, $slot->fresh()->status);
    }

    public function test_does_not_touch_already_confirmed_bookings(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::BOOKED]);
        $customer = Customer::factory()->create();

        $booking = Booking::create([
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::CONFIRMED,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $booking->forceFill(['created_at' => now()->subMinutes(20)])->save();

        $released = $this->bookingService->releaseExpiredReservations(15);

        $this->assertEquals(0, $released);
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->fresh()->status);
        $this->assertEquals(SlotStatus::BOOKED, $slot->fresh()->status);
    }

    public function test_skips_a_booking_currently_being_confirmed_by_another_process(): void
    {
        $slot = Slot::factory()->create(['status' => SlotStatus::PENDING]);
        $customer = Customer::factory()->create();

        $booking = Booking::create([
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PENDING,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $booking->forceFill(['created_at' => now()->subMinutes(20)])->save();

        $externalLock = Cache::lock("booking:confirm:{$booking->id}", 30);
        $externalLock->get();

        try {
            $released = $this->bookingService->releaseExpiredReservations(15);

            $this->assertEquals(0, $released);
            $this->assertEquals(BookingStatus::PENDING, $booking->fresh()->status);
            $this->assertEquals(SlotStatus::PENDING, $slot->fresh()->status);
        } finally {
            $externalLock->release();
        }
    }

    public function test_releases_multiple_expired_reservations_in_one_run(): void
    {
        $customer = Customer::factory()->create();

        $expiredBookings = collect(range(1, 3))->map(function () use ($customer) {
            $slot = Slot::factory()->create(['status' => SlotStatus::PENDING]);

            $booking = Booking::create([
                'slot_id' => $slot->id,
                'customer_id' => $customer->id,
                'status' => BookingStatus::PENDING,
                'idempotency_key' => (string) Str::uuid(),
            ]);
            $booking->forceFill(['created_at' => now()->subMinutes(30)])->save();

            return $booking;
        });

        $released = $this->bookingService->releaseExpiredReservations(15);

        $this->assertEquals(3, $released);

        foreach ($expiredBookings as $booking) {
            $this->assertEquals(BookingStatus::CANCELLED, $booking->fresh()->status);
            $this->assertEquals(SlotStatus::AVAILABLE, $booking->fresh()->slot->status);
        }
    }
}
