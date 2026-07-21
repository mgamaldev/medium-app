<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Customer;
use App\Models\Slot;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $slotId = $request->validated('slot_id');
        $customerId = $request->validated('customer_id');

        $slot = Slot::findOrFail($slotId);
        $customer = Customer::findOrFail($customerId);

        try {
            $booking = $this->bookingService->createBooking($slot, $customer);

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
