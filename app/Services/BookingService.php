<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(Slot $slot, Customer $customer): Booking
    {
        $lockKey = "booking:slot:{$slot->id}";
        $lock = Cache::lock($lockKey, 10);

        try {
            return $lock->block(10, function () use ($slot, $customer) {

                return DB::transaction(function () use ($slot, $customer) {

                    $freshSlot = $slot->fresh();

                    if (! $freshSlot || $freshSlot->status !== SlotStatus::AVAILABLE) {
                        throw new \Exception('Slot is not available');
                    }
                    $booking = Booking::create([
                        'slot_id' => $freshSlot->id,
                        'customer_id' => $customer->id,
                        'status' => BookingStatus::CONFIRMED,
                    ]);

                    $freshSlot->update(['status' => SlotStatus::BOOKED]);

                    return $booking;
                });
            });
        } catch (LockTimeoutException $e) {
            throw new \Exception('Excuse for getting the lock, the crowd is very high');
        }
    }
}
