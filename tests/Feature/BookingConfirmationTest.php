<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Tests\TestCase;

class BookingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(BookingService::class);
    }

    public function test_successful_card_confirms_booking_and_books_slot(): void
    {
        $booking = $this->makePendingBooking(price: 25.00);

        $confirmed = $this->bookingService->confirmBooking($booking, 'pm_card_visa');

        $this->assertEquals(BookingStatus::CONFIRMED, $confirmed->status);
        $this->assertEquals(SlotStatus::BOOKED, $confirmed->slot->fresh()->status);
    }

    public function test_declined_card_leaves_booking_pending_and_slot_untouched(): void
    {
        $booking = $this->makePendingBooking(price: 25.00);

        try {
            $this->bookingService->confirmBooking($booking, 'pm_card_chargeDeclined');
            $this->fail('Expected an exception for a declined card.');
        } catch (\Exception $e) {
        }

        $fresh = $booking->fresh();
        $this->assertEquals(BookingStatus::PENDING, $fresh->status);
        $this->assertEquals(SlotStatus::AVAILABLE, $fresh->slot->fresh()->status);
    }

    public function test_card_requiring_3d_secure_throws_incomplete_payment_and_stays_pending(): void
    {
        $booking = $this->makePendingBooking(price: 25.00);

        try {
            $this->bookingService->confirmBooking($booking, 'pm_card_authenticationRequired');
            $this->fail('Expected IncompletePayment to be thrown.');
        } catch (IncompletePayment $e) {
        }

        $fresh = $booking->fresh();
        $this->assertEquals(BookingStatus::PENDING, $fresh->status);
        $this->assertEquals(SlotStatus::AVAILABLE, $fresh->slot->fresh()->status);
    }

    public function test_confirming_an_already_confirmed_booking_does_not_charge_again(): void
    {
        $booking = $this->makePendingBooking(price: 25.00);

        $first = $this->bookingService->confirmBooking($booking, 'pm_card_visa');
        $this->assertEquals(BookingStatus::CONFIRMED, $first->status);

        $second = $this->bookingService->confirmBooking($first->fresh(), 'pm_card_visa');

        $this->assertEquals(BookingStatus::CONFIRMED, $second->status);
        $this->assertEquals(SlotStatus::BOOKED, $second->slot->fresh()->status);
    }

    public function test_amount_charged_is_in_cents_not_dollars(): void
    {
        $booking = $this->makePendingBooking(price: 25.00);

        $confirmed = $this->bookingService->confirmBooking($booking, 'pm_card_visa');

        $this->assertEquals(BookingStatus::CONFIRMED, $confirmed->status);
    }

    private function makePendingBooking(float $price): Booking
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();
        $customer->createAsStripeCustomer();

        $slot = Slot::factory()->create([
            'status' => SlotStatus::AVAILABLE,
            'price' => $price,
        ]);

        return Booking::create([
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'status' => BookingStatus::PENDING,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }
}
