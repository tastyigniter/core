<?php

declare(strict_types=1);

namespace Igniter\System\Database\Factories;

use Override;
use Igniter\Flame\Database\Factories\Factory;
use Igniter\System\Models\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    #[Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->lexify('??????'),
            'code' => $this->faker->languageCode(),
            'idiom' => $this->faker->languageCode(),
            'status' => $this->faker->boolean(),
        ];
    }
}
