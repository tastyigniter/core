<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Model\Bounds;

class Circle implements Contracts\CircleInterface
{
    const TYPE = 'CIRCLE';

    protected CoordinatesInterface $coordinate;

    protected int $radius;

    protected ?string $unit = null;

    protected int $precision = 8;

    public function __construct(array|CoordinatesInterface $coordinate, int $radius)
    {
        if ($coordinate instanceof Contracts\CoordinatesInterface) {
            $this->coordinate = $coordinate;
        } else {
            [$latitude, $longitude] = $coordinate;
            $this->coordinate = new Model\Coordinates($latitude, $longitude);
        }

        $this->radius = $radius;
    }

    public function getRadius(): int
    {
        return $this->radius;
    }

    /**
     * Returns the geometry type.
     */
    public function getGeometryType(): string
    {
        return static::TYPE;
    }

    /**
     * Returns the precision of the geometry.
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): Circle
    {
        $this->precision = $precision;

        return $this;
    }

    public function getCoordinate(): ?CoordinatesInterface
    {
        return $this->coordinate;
    }

    public function getCoordinates(): Model\CoordinatesCollection
    {
        return new Model\CoordinatesCollection([$this->getCoordinate()]);
    }

    public function setCoordinate(CoordinatesInterface $coordinate)
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    //
    //
    //

    /**
     * Returns true if the geometry is empty.
     */
    public function isEmpty(): bool
    {
        return !$this->getCoordinate()->getLatitude()
            || !$this->getCoordinate()->getLongitude()
            || !$this->getRadius();
    }

    public function distanceUnit($unit): Contracts\CircleInterface
    {
        $this->unit = $unit;

        return $this;
    }

    public function pointInRadius(Contracts\CoordinatesInterface $coordinate): bool
    {
        $distance = new Distance;
        $distance->in($this->unit)
            ->setFrom($coordinate)
            ->setTo($this->getCoordinate());

        $radius = $distance->convertToUserUnit($this->getRadius());

        return $distance->haversine() <= $radius;
    }

    /**
     * Returns the bounding box of the Geometry
     */
    public function getBounds(): ?Bounds
    {
        return null;
    }
}
