<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class Customer extends Model
{
    use Billable, HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'timezone',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
