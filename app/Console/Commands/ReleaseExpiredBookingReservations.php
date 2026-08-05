<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ReleaseExpiredBookingReservations extends Command
{
    protected $signature = 'bookings:release-expired {--minutes= : Override the reservation TTL in minutes}';

    protected $description = 'Cancel PENDING bookings past the reservation TTL and free their slots back to AVAILABLE.';

    public function handle(BookingService $bookingService): int
    {
        $minutes = $this->option('minutes');

        $released = $bookingService->releaseExpiredReservations(
            $minutes !== null ? (int) $minutes : null
        );

        $this->info("Released {$released} expired booking reservation(s).");

        return self::SUCCESS;
    }
}
