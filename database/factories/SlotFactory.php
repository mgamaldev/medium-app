<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    protected $model = Slot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = now()->addDays()->setHour(10)->setMinutes(0)->setSeconds(0);

        return [
            'resource_id' => $this->faker->numberBetween(1, 10),
            'starts_at' => $startTime,
            'ends_at' => (clone $startTime)->addHours(1),
            'status' => SlotStatus::AVAILABLE,
        ];
    }
}
