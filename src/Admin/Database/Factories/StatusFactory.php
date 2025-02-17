<?php

declare(strict_types=1);

namespace Igniter\Admin\Database\Factories;

use Igniter\Admin\Models\Status;
use Igniter\Flame\Database\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'status_name' => $this->faker->sentence(2),
            'status_for' => $this->faker->randomElement(['order', 'reservation']),
            'status_color' => $this->faker->hexColor(),
            'status_comment' => $this->faker->paragraph(),
            'notify_customer' => $this->faker->boolean(),
        ];
    }
}
