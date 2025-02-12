<?php

namespace Igniter\Main\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class ThemeFactory extends Factory
{
    protected $model = \Igniter\Main\Models\Theme::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'code' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'version' => $this->faker->semver(),
            'data' => [],
            'status' => true,
            'is_default' => false,
        ];
    }
}
