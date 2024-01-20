<?php

namespace Igniter\Admin\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class StatusHistoryFactory extends Factory
{
    protected $model = \Igniter\Admin\Models\StatusHistory::class;

    public function definition(): array
    {
        return [
            'object_id' => $this->faker->numberBetween(1, 9999),
            'object_type' => $this->faker->randomElement(['order', 'reservation']),
            'user_id' => $this->faker->numberBetween(1, 9999),
            'status_id' => $this->faker->numberBetween(1, 9999),
            'notify' => $this->faker->boolean(),
            'comment' => $this->faker->paragraph(),
        ];
    }
}
