<?php

namespace App\Enums;

enum SlotStatus: string
{
    case AVAILABLE = 'available';
    case PENDING = 'pending';
    case BOOKED = 'booked';
    case CANCELLED = 'cancelled';
}
