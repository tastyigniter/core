<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Igniter\Flame\Geolite\Geocoder limit(int $limit)
 * @method static \Igniter\Flame\Geolite\Geocoder locale(string $locale)
 * @method static \Illuminate\Support\Collection geocode(string $address)
 * @method static \Illuminate\Support\Collection reverse(int|float $latitude, int|float $longitude)
 * @method static \Illuminate\Support\Collection geocodeQuery(\Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query)
 * @method static \Illuminate\Support\Collection reverseQuery(\Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query)
 * @method static \Igniter\Flame\Geolite\Contracts\AbstractProvider using(string $name)
 * @method static \Igniter\Flame\Geolite\Contracts\AbstractProvider driver(string $driver = null)
 * @method static \Igniter\Flame\Geolite\Contracts\AbstractProvider makeProvider(string $name)
 * @method static string getDefaultDriver()
 * @method static \Igniter\Flame\Geolite\Geocoder extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \Igniter\Flame\Geolite\Geocoder setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Igniter\Flame\Geolite\Geocoder forgetDrivers()
 *
 * @see \Igniter\Flame\Geolite\Geocoder
 */
class Geocoder extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'geocoder';
    }
}
