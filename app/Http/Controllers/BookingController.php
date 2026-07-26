<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
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

        $idempotencyKey = $request->header('Idempotency-Key', (string) Str::uuid());

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
}
