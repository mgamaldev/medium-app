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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;

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

                    $freshSlot->update([
                        'status' => SlotStatus::PENDING,
                    ]);

                    return Booking::create([
                        'slot_id' => $freshSlot->id,
                        'customer_id' => $customer->id,
                        'status' => BookingStatus::PENDING,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                });
            });
        } catch (LockTimeoutException $e) {
            throw new \Exception('The slot is currently being booked by another user. Please try again.');
        } catch (QueryException $e) { // @phpstan-ignore-line catch.neverThrown
            $existingBooking = Booking::where('idempotency_key', $idempotencyKey)->first();

            if ($existingBooking) {
                return $existingBooking;
            }
            throw $e;
        }
    }

    public function confirmBooking(Booking $booking, string $paymentMethodId): Booking
    {
        if ($booking->status !== BookingStatus::PENDING) {
            return $booking;
        }

        $amountInCents = (int) round($booking->slot->price * 100);

        $customer = $booking->customer;

        try {
            $customer->charge($amountInCents, $paymentMethodId, [
                'payment_method_types' => ['card'],
            ]);

            return DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => BookingStatus::CONFIRMED,
                ]);

                $booking->slot->update([
                    'status' => SlotStatus::BOOKED,
                ]);

                DB::afterCommit(function () use ($booking) {
                    event(new BookingCreated($booking));
                });

                return $booking;
            });
        } catch (IncompletePayment $e) {
            throw $e;
        } catch (\Exception $exception) {
            Log::error("Payment failed for booking {$booking->id}: ".$exception->getMessage());
            throw new \Exception('Payment processing failed. Please check your card details.');
        }
    }

    public function releaseExpiredReservations(?int $ttlMinutes = null): int
    {
        $ttlMinutes ??= (int) config('booking.reservation_ttl_minutes', 15);
        $cutoff = now()->subMinutes($ttlMinutes);

        $expiredBookingIds = Booking::where('status', BookingStatus::PENDING)
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        $releasedCount = 0;

        foreach ($expiredBookingIds as $bookingId) {
            if ($this->releaseExpiredBooking($bookingId, $cutoff)) {
                $releasedCount++;
            }
        }

        return $releasedCount;
    }

    private function releaseExpiredBooking(int $bookingId, Carbon $cutoff): bool
    {
        $lockKey = "booking:confirm:{$bookingId}";
        $lock = Cache::lock($lockKey, 20);

        try {
            return (bool) $lock->block(5, function () use ($bookingId, $cutoff) {
                $booking = Booking::find($bookingId);

                if (! $booking || $booking->status !== BookingStatus::PENDING) {
                    return false;
                }

                if ($booking->created_at->gt($cutoff)) {
                    return false;
                }

                return DB::transaction(function () use ($booking) {
                    $booking->update([
                        'status' => BookingStatus::CANCELLED,
                    ]);

                    $slot = $booking->slot;

                    if ($slot->status === SlotStatus::PENDING) {
                        $slot->update([
                            'status' => SlotStatus::AVAILABLE,
                        ]);
                    }

                    return true;
                });
            });
        } catch (LockTimeoutException $e) {
            return false;
        }
    }
}
