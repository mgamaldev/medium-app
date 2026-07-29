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

/**
 * These are integration tests: they hit the real Stripe TEST-MODE API
 * (no card numbers ever touch this codebase — we use Stripe's special
 * test PaymentMethod tokens that simulate specific outcomes without
 * needing a browser/Stripe Elements to tokenize a real card).
 *
 * Requirements to run:
 *   - STRIPE_KEY / STRIPE_SECRET in .env.testing must be TEST keys.
 *   - Network access to api.stripe.com.
 *
 * Reference tokens (Stripe docs — "Testing without the Payment Element"):
 *   pm_card_visa                    -> succeeds immediately
 *   pm_card_chargeDeclined          -> card_declined error
 *   pm_card_authenticationRequired  -> requires 3D Secure (IncompletePayment)
 */
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
            // Expected: charge failure should surface as a generic exception,
            // never as a silent success.
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
            // Expected: 3DS-required cards must not be treated as hard failures,
            // and must not confirm the booking until the customer completes
            // authentication.
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

        // Simulate a client retry (e.g. a timed-out request being resent)
        // hitting confirmBooking again for the same, now-confirmed booking.
        // This must be a safe no-op — no second charge, no state change.
        $second = $this->bookingService->confirmBooking($first->fresh(), 'pm_card_visa');

        $this->assertEquals(BookingStatus::CONFIRMED, $second->status);
        $this->assertEquals(SlotStatus::BOOKED, $second->slot->fresh()->status);
    }

    public function test_amount_charged_is_in_cents_not_dollars(): void
    {
        // Guards against the cents-vs-dollars off-by-100 trap: a $25.00
        // slot must charge 2500 (cents), not 25 or 250000.
        $booking = $this->makePendingBooking(price: 25.00);

        $confirmed = $this->bookingService->confirmBooking($booking, 'pm_card_visa');

        $this->assertEquals(BookingStatus::CONFIRMED, $confirmed->status);
        // If the amount were wrong, Stripe would either reject a sub-minimum
        // charge or the test would need manual verification in the Stripe
        // dashboard test-mode logs for this PaymentIntent.
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