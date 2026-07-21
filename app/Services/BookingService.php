<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(Slot $slot, Customer $customer): Booking
    {
        if ($slot->status !== SlotStatus::AVAILABLE) {
            throw new \Exception('Slot is not available');
        }

        return DB::transaction(function () use ($slot, $customer) {

            $booking = Booking::create([
                'slot_id' => $slot->id,
                'customer_id' => $customer->id,
                'status' => BookingStatus::CONFIRMED,
            ]);

            $slot->update(['status' => SlotStatus::BOOKED]);

            return $booking;
        });

    }
}
