<?php

namespace Igniter\Flame\Geolite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Igniter\Flame\Geolite\Contracts\DistanceInterface distance()
 * @method static \Igniter\Flame\Geolite\Contracts\CircleInterface circle(\Igniter\Flame\Geolite\Contracts\CoordinatesInterface|array $coordinate, int $radius)
 * @method static \Igniter\Flame\Geolite\Contracts\PolygonInterface polygon(\Igniter\Flame\Geolite\Contracts\CoordinatesInterface|array $coordinates)
 * @method static \Igniter\Flame\Geolite\Contracts\VertexInterface vertex()
 * @method static \Igniter\Flame\Geolite\Contracts\CoordinatesInterface coordinates(int|float|null $latitude, int|float|null $longitude)
 * @method static \Igniter\Flame\Geolite\AddressMatch addressMatch(void $components)
 *
 * @see \Igniter\Flame\Geolite\Geolite
 */
class Geolite extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'geolite';
    }
}
