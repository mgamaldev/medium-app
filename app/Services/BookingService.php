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
        return DB::transaction(function () use ($slot, $customer) {

            $lockedSlot = Slot::where('id', $slot->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSlot->status !== SlotStatus::AVAILABLE) {
                throw new \Exception('Slot is not available');
            }
            $booking = Booking::create([
                'slot_id' => $lockedSlot->id,
                'customer_id' => $customer->id,
                'status' => BookingStatus::CONFIRMED,
            ]);

            $lockedSlot->update(['status' => SlotStatus::BOOKED]);

            return $booking;
        });

    }
}
