<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property SlotStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $price
 */
class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'starts_at',
        'ends_at',
        'status',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => SlotStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', SlotStatus::AVAILABLE)->where('starts_at', '>', now()->utc());
    }

    public function scopeForResource(Builder $query, int $resourceId): Builder
    {
        return $query->where('resource_id', $resourceId);
    }
}
