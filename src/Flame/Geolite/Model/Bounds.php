<?php

namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Contracts\PolygonInterface;
use Igniter\Flame\Geolite\Polygon;

class Bounds implements Contracts\BoundsInterface
{
    protected int $precision = 8;

    /**
     * @param ?float $south South bound, also min latitude
     * @param ?float $west West bound, also min longitude
     * @param ?float $north North bound, also max latitude
     * @param ?float $east East bound, also max longitude
     */
    public function __construct(
        protected null|int|float $south,
        protected null|int|float $west,
        protected null|int|float $north,
        protected null|int|float $east
    ) {
        $this->south = (float)$south;
        $this->west = (float)$west;
        $this->north = (float)$north;
        $this->east = (float)$east;
    }

    public static function fromPolygon(Contracts\PolygonInterface $polygon): self
    {
        $bounds = new static(null, null, null, null);
        $bounds->setPolygon($polygon);

        return $bounds;
    }

    public function setNorth(int|float $north): self
    {
        $this->north = $north;

        return $this;
    }

    public function setEast(int|float $east): self
    {
        $this->east = $east;

        return $this;
    }

    public function setSouth(int|float $south): self
    {
        $this->south = $south;

        return $this;
    }

    public function setWest(int|float $west): self
    {
        $this->west = $west;

        return $this;
    }

    /**
     * Returns the south bound.
     */
    public function getSouth(): int|float
    {
        return $this->south;
    }

    /**
     * Returns the west bound.
     */
    public function getWest(): int|float
    {
        return $this->west;
    }

    /**
     * Returns the north bound.
     */
    public function getNorth(): int|float
    {
        return $this->north;
    }

    /**
     * Returns the east bound.
     */
    public function getEast(): int|float
    {
        return $this->east;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }

    public function pointInBounds(Contracts\CoordinatesInterface $coordinate): bool
    {
        return !(bccomp($coordinate->getLatitude(), $this->getSouth(), $this->getPrecision()) === -1
            || bccomp($coordinate->getLatitude(), $this->getNorth(), $this->getPrecision()) === 1
            || bccomp($coordinate->getLongitude(), $this->getEast(), $this->getPrecision()) === 1
            || bccomp($coordinate->getLongitude(), $this->getWest(), $this->getPrecision()) === -1);
    }

    public function getAsPolygon(): PolygonInterface
    {
        $northWest = new Coordinates($this->north, $this->west);

        return new Polygon(
            new CoordinatesCollection([
                $northWest,
                new Coordinates($this->north, $this->east),
                new Coordinates($this->south, $this->east),
                new Coordinates($this->south, $this->west),
                $northWest,
            ])
        );
    }

    public function setPolygon(Contracts\PolygonInterface $polygon)
    {
        foreach ($polygon->getCoordinates() as $coordinate) {
            $this->addCoordinate($coordinate);
        }
    }

    public function merge(Contracts\BoundsInterface $bounds): self
    {
        $cBounds = clone $this;

        $newCoordinates = $bounds->getAsPolygon()->getCoordinates();
        foreach ($newCoordinates as $coordinate) {
            $cBounds->addCoordinate($coordinate);
        }

        return $cBounds;
    }

    /**
     * Returns an array with bounds.
     */
    public function toArray(): array
    {
        return [
            'south' => $this->getSouth(),
            'west' => $this->getWest(),
            'north' => $this->getNorth(),
            'east' => $this->getEast(),
        ];
    }

    protected function addCoordinate(Contracts\CoordinatesInterface $coordinate)
    {
        $latitude = $coordinate->getLatitude();
        $longitude = $coordinate->getLongitude();

        if (!$this->north && !$this->south && !$this->east && !$this->west) {
            $this->setNorth($latitude);
            $this->setSouth($latitude);
            $this->setEast($longitude);
            $this->setWest($longitude);
        } else {
            if (bccomp($latitude, $this->getSouth(), $this->getPrecision()) === -1) {
                $this->setSouth($latitude);
            }

            if (bccomp($latitude, $this->getNorth(), $this->getPrecision()) === 1) {
                $this->setNorth($latitude);
            }

            if (bccomp($longitude, $this->getEast(), $this->getPrecision()) === 1) {
                $this->setEast($longitude);
            }

            if (bccomp($longitude, $this->getWest(), $this->getPrecision()) === -1) {
                $this->setWest($longitude);
            }
        }
    }
}
