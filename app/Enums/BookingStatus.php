<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case BOOKED = 'booked';
    case CANCELLED = 'cancelled';
}
