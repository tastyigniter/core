<?php

declare(strict_types=1);

namespace Igniter\Main\Database\Factories;

use Override;
use Igniter\Flame\Database\Factories\Factory;
use Igniter\Main\Models\Theme;

class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    #[Override]
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
