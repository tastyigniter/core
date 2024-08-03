<?php

namespace Igniter\Flame\Currency\Converters;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

abstract class AbstractConverter
{
    /**
     * Returns information about the converter
     * Must return array:
     *
     * [
     *      'name'        => 'Open Exchange Rates',
     *      'description' => 'Conversion services provided by Open Exchange Rates.'
     * ]
     */
    abstract public function converterDetails(): array;

    /**
     * Returns list of exchange rates for currencies specified.
     */
    abstract public function getExchangeRates(string $base, array $currencies): array;

    //
    //
    //

    public function getName(): string
    {
        return array_get($this->converterDetails(), 'name', 'Undefined name');
    }

    public function getDescription(): string
    {
        return array_get($this->converterDetails(), 'description', 'Undefined description');
    }

    protected function getHttpClient(): Client
    {
        return new Client;
    }

    //
    //
    //

    /**
     * Forget the repository cache.
     */
    public function forgetCache(): self
    {
        if ($this->getCacheLifetime()) {
            // Flush cache keys, then forget actual cache
            $this->getCacheDriver()->forget($this->getCacheKey());
        }

        return $this;
    }

    public function getCacheKey(): string
    {
        return sprintf('igniter.currency.rates.%s', str_slug($this->getName()));
    }

    /**
     * Get the cache lifetime.
     */
    public function getCacheLifetime(): int
    {
        return config('currency.ratesCacheDuration', 0);
    }

    protected function cacheCallback(string $cacheKey, \Closure $closure): mixed
    {
        if (!$lifetime = $this->getCacheLifetime()) {
            return $closure();
        }

        $cacheKey = $this->getCacheKey().'@'.md5($cacheKey);

        return $this->getCacheDriver()->remember($cacheKey, $lifetime, $closure);
    }

    protected function getCacheDriver(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::driver(config('currency.cache_driver'));
    }
}
