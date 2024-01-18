<?php

namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;

class Coordinates implements CoordinatesInterface
{
    public function __construct(
        private null|int|float $latitude,
        private null|int|float $longitude,
        private ?Ellipsoid $ellipsoid = null,
        private int $precision = 0
    ) {
        $this->latitude = $this->normalizeLatitude($latitude);
        $this->longitude = $this->normalizeLongitude($longitude);
        $this->ellipsoid = $ellipsoid ?: Ellipsoid::createFromName(Ellipsoid::WGS84);
    }

    /**
     * Set the latitude.
     */
    public function setLatitude(int|float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Set the longitude.
     */
    public function setLongitude(int|float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function setPrecision(int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Returns the latitude.
     */
    public function getLatitude(): int|float
    {
        return $this->latitude;
    }

    /**
     * Returns the longitude.
     */
    public function getLongitude(): int|float
    {
        return $this->longitude;
    }

    public function getEllipsoid(): Ellipsoid
    {
        return $this->ellipsoid;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    /**
     * Returns a boolean determining coordinates equality
     */
    public function isEqual(CoordinatesInterface $coordinate): bool
    {
        return bccomp($this->latitude, $coordinate->getLatitude(), $this->getPrecision()) === 0
            && bccomp($this->longitude, $coordinate->getLongitude(), $this->getPrecision()) === 0;
    }

    /**
     * Normalizes a latitude to the (-90, 90) range.
     * Latitudes below -90.0 or above 90.0 degrees are capped, not wrapped.
     */
    public function normalizeLatitude(int|float $latitude): int|float
    {
        return (float)max(-90, min(90, $latitude));
    }

    /**
     * Normalizes a longitude to the (-180, 180) range.
     * Longitudes below -180.0 or abode 180.0 degrees are wrapped.
     */
    public function normalizeLongitude(int|float $longitude): int|float
    {
        if ($longitude % 360 === 180) {
            return 180.0;
        }

        $mod = fmod($longitude, 360);
        $fallback = $mod > 180 ? $mod - 360 : $mod;
        $longitude = $mod < -180 ? $mod + 360 : $fallback;

        return (float)$longitude;
    }

    /**
     * Returns the coordinates as a tuple
     */
    public function toArray(): array
    {
        return [$this->getLongitude(), $this->getLatitude()];
    }

    protected function typeToString(mixed $value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
