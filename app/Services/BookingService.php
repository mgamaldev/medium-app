<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Slot;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(Slot $slot, Customer $customer, string $idempotencyKey): Booking
    {

        $existingBooking = Booking::where('idempotency_key', $idempotencyKey)->first();

        if ($existingBooking) {
            return $existingBooking;
        }

        $lockKey = "booking:slot:{$slot->id}";
        $lock = Cache::lock($lockKey, 10);

        try {
            return $lock->block(10, function () use ($slot, $customer, $idempotencyKey) {

                return DB::transaction(function () use ($slot, $customer, $idempotencyKey) {

                    $freshSlot = $slot->fresh();

                    if (! $freshSlot || $freshSlot->status !== SlotStatus::AVAILABLE) {
                        throw new \Exception('Slot is not available');
                    }
                    $booking = Booking::create([
                        'slot_id' => $freshSlot->id,
                        'customer_id' => $customer->id,
                        'status' => BookingStatus::CONFIRMED,
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    $freshSlot->update(['status' => SlotStatus::BOOKED]);

                    DB::afterCommit(function () use ($booking) {
                        event(new BookingCreated($booking));
                    });

                    return $booking;
                });
            });
        } catch (LockTimeoutException $e) {
            throw new \Exception('Excuse for getting the lock, the crowd is very high');
        } catch (QueryException $e) { // @phpstan-ignore-line catch.neverThrown
            $existingBooking = Booking::where('idempotency_key', $idempotencyKey)->first();

            if ($existingBooking) {
                return $existingBooking;
            }
            throw $e;
        }
    }
}
