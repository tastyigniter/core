<?php

namespace Igniter\System\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class LanguageFactory extends Factory
{
    protected $model = \Igniter\System\Models\Language::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->lexify('??????'),
            'code' => $this->faker->languageCode(),
            'status' => $this->faker->boolean(),
        ];
    }
}
