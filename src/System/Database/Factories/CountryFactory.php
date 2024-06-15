<?php

namespace Igniter\System\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = \Igniter\System\Models\Country::class;

    public function definition(): array
    {
        return [
            'country_name' => $this->faker->sentence(2),
            'iso_code_2' => $this->faker->lexify('??'),
            'iso_code_3' => $this->faker->countryISOAlpha3(),
            'priority' => $this->faker->randomDigit(),
            'status' => true,
        ];
    }
}
