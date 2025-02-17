<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\CircleInterface;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Model\Bounds;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\CoordinatesCollection;

class Circle implements CircleInterface
{
    const TYPE = 'CIRCLE';

    protected CoordinatesInterface $coordinate;

    protected int $radius;

    protected ?string $unit = null;

    protected int $precision = 8;

    public function __construct(array|CoordinatesInterface $coordinate, int $radius)
    {
        if ($coordinate instanceof CoordinatesInterface) {
            $this->coordinate = $coordinate;
        } else {
            [$latitude, $longitude] = $coordinate;
            $this->coordinate = new Coordinates($latitude, $longitude);
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

    public function getCoordinates(): CoordinatesCollection
    {
        return new CoordinatesCollection([$this->getCoordinate()]);
    }

    public function setCoordinate(CoordinatesInterface $coordinate): static
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

    public function distanceUnit($unit): CircleInterface
    {
        $this->unit = $unit;

        return $this;
    }

    public function pointInRadius(CoordinatesInterface $coordinate): bool
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
