<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking Reservation TTL
    |--------------------------------------------------------------------------
    |
    | How long (in minutes) a slot stays reserved (PENDING) for a customer
    | who created a booking but never confirmed payment. After this window,
    | the scheduled job releases the slot back to AVAILABLE and cancels the
    | stale booking so other customers can book it.
    |
    */

    'reservation_ttl_minutes' => env('BOOKING_RESERVATION_TTL_MINUTES', 15),

];
