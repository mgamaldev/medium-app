<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property BookingStatus $status
 * @property string $idempotency_key
 * @property-read Slot $slot
 * @property-read Customer $customer
 */
class Booking extends Model
{
    protected $fillable = [
        'slot_id',
        'customer_id',
        'status',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }
}
