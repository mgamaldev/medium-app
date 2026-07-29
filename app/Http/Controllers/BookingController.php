<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Exceptions\IncompletePayment;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $idempotencyKey = (string) $request->header('Idempotency-Key');

        if (trim($idempotencyKey) === '') {
            return response()->json([
                'message' => 'The Idempotency-Key header is required.',
            ], 422);
        }

        $slotId = $request->validated('slot_id');

        /** @var User $user */
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer instanceof Customer) {
            return response()->json([
                'message' => 'Customer profile not found for this user.',
            ], 404);
        }

        $slot = Slot::findOrFail($slotId);

        try {
            $booking = $this->bookingService->createBooking($slot, $customer, $idempotencyKey);

            return response()->json([
                'message' => 'Booking created successfully',
                'data' => $booking->load('slot', 'customer'),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirm(StoreBookingRequest $request, Booking $booking, BookingService $bookingService)
    {
        /** @var User $user */
        $user = $request->user();
        if ($booking->customer_id !== $user->customer?->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm this booking.',
            ], 403);
        }

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            $booking = $bookingService->confirmBooking(
                $booking,
                $request->input('payment_method_id')
            );

            return response()->json([
                'message' => 'Booking confirmed successfully!',
                'booking' => $booking,
            ]);
        } catch (IncompletePayment $exception) {
            $paymentIntent = $exception->payment->asStripePaymentIntent();

            return response()->json([
                'requires_action' => true,
                'payment_intent' => $paymentIntent,
                'redirect_url' => route('cashier.payment', [$paymentIntent, 'redirect' => route('bookings.index')]),
            ], 402);
        }
    }
}
