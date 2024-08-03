<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\CircleInterface;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\PolygonInterface;
use Igniter\Flame\Geolite\Contracts\VertexInterface;
use Igniter\Flame\Geolite\Model\Coordinates;

class Geolite
{
    /**
     * The ratio meters per mile.
     *
     * @var float
     */
    public const METERS_PER_MILE = 1609.344;

    /**
     * The ratio feet per meter.
     *
     * @var float
     */
    public const FEET_PER_METER = 0.3048;

    /**
     * The kilometer unit.
     *
     * @var string
     */
    public const KILOMETER_UNIT = 'km';

    /**
     * The mile unit.
     *
     * @var string
     */
    public const MILE_UNIT = 'mi';

    /**
     * The feet unit.
     *
     * @var string
     */
    public const FOOT_UNIT = 'ft';

    public function distance(): DistanceInterface
    {
        return new Distance;
    }

    public function circle(array|CoordinatesInterface $coordinate, int $radius): CircleInterface
    {
        return (new Circle($coordinate, $radius))
            ->setPrecision(config('igniter-geocoder.precision', 8));
    }

    public function polygon(array|CoordinatesInterface $coordinates): PolygonInterface
    {
        return (new Polygon($coordinates))
            ->setPrecision(config('igniter-geocoder.precision', 8));
    }

    public function vertex(): VertexInterface
    {
        return (new Vertex)
            ->setPrecision(config('igniter-geocoder.precision', 8));
    }

    public function coordinates(null|int|float $latitude, null|int|float $longitude): CoordinatesInterface
    {
        return (new Coordinates($latitude, $longitude))
            ->setPrecision(config('igniter-geocoder.precision', 8));
    }

    public function addressMatch($components): AddressMatch
    {
        return new AddressMatch($components);
    }
}
