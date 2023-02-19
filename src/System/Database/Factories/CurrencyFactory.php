<?php

namespace Igniter\System\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = \Igniter\System\Models\Currency::class;

    public function definition(): array
    {
        return [
            'currency_name' => $this->faker->sentence(2),
            'currency_code' => $this->faker->currencyCode(),
            'currency_symbol' => '&pound;',
            'country_id' => 1,
            'symbol_position' => $this->faker->lexify('?'),
            'currency_rate' => 1,
            'thousand_sign' => $this->faker->lexify('?'),
            'decimal_sign' => '.',
            'decimal_position' => $this->faker->numerify('#'),
            'currency_status' => $this->faker->boolean(),
        ];
    }
}
