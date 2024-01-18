<?php

namespace Igniter\Flame\Currency;

use Igniter\Flame\Currency\Converters\AbstractConverter;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;

class Converter extends Manager
{
    public function getExchangeRates($base, Collection $currencies)
    {
        $currencies = ($currencies->map->getCode())->all();

        return $this->driver()->getExchangeRates($base, $currencies);
    }

    /**
     * Get a driver instance.
     */
    public function driver(mixed $driver = null): AbstractConverter
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->createDriver($driver);
    }

    public function getDefaultDriver(): string
    {
        return $this->container['config']['currency.converter'] ?? 'openexchangerates';
    }

    public function createOpenExchangeRatesDriver(): AbstractConverter
    {
        $config = $this->container['config']['currency.converters.openexchangerates'];

        return new $config['class']($config);
    }

    public function createFixerIODriver(): AbstractConverter
    {
        $config = $this->container['config']['currency.converters.fixerio'];

        return new $config['class']($config);
    }
}
