<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\BoundsInterface;
use Igniter\Flame\Geolite\Contracts\PolygonInterface;
use Traversable;

class Polygon implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, PolygonInterface
{
    public const TYPE = 'POLYGON';

    protected Model\CoordinatesCollection $coordinates;

    protected BoundsInterface $bounds;

    protected bool $hasCoordinate = false;

    protected ?int $precision = 8;

    /**
     * @param null|array|Model\CoordinatesCollection $coordinates
     */
    public function __construct($coordinates = null)
    {
        if ($coordinates instanceof Model\CoordinatesCollection) {
            $this->coordinates = $coordinates;
        } elseif (is_array($coordinates) || is_null($coordinates)) {
            $this->coordinates = new Model\CoordinatesCollection([]);
        } else {
            throw new \InvalidArgumentException;
        }

        $this->bounds = Model\Bounds::fromPolygon($this);

        if (is_array($coordinates)) {
            $this->put($coordinates);
        }
    }

    public function getGeometryType(): string
    {
        return self::TYPE;
    }

    public function getCoordinate(): ?Contracts\CoordinatesInterface
    {
        return $this->coordinates->offsetGet(0);
    }

    public function getCoordinates(): Model\CoordinatesCollection
    {
        return $this->coordinates;
    }

    public function setCoordinates(Model\CoordinatesCollection $coordinates)
    {
        $this->coordinates = $coordinates;
        $this->bounds->setPolygon($this);

        return $this;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): self
    {
        $this->bounds->setPrecision($precision);
        $this->precision = $precision;

        return $this;
    }

    public function getBounds(): BoundsInterface
    {
        return $this->bounds;
    }

    public function setBounds(BoundsInterface $bounds): self
    {
        $this->bounds = $bounds;

        return $this;
    }

    //
    //
    //

    public function get(int $key): ?Contracts\CoordinatesInterface
    {
        return $this->coordinates->get($key);
    }

    public function put(int|array $key, ?Contracts\CoordinatesInterface $coordinate = null)
    {
        if (is_array($key)) {
            $values = $key;
        } elseif ($coordinate !== null) {
            $values = [$key => $coordinate];
        } else {
            throw new \InvalidArgumentException;
        }

        foreach ($values as $index => $value) {
            if (!$value instanceof Contracts\CoordinatesInterface) {
                [$latitude, $longitude] = $value;
                $value = new Model\Coordinates($latitude, $longitude);
            }

            $this->coordinates->put($index, $value);
        }

        $this->bounds->setPolygon($this);
    }

    public function push(Contracts\CoordinatesInterface $coordinate)
    {
        $coordinates = $this->coordinates->push($coordinate);

        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    public function forget($key)
    {
        $coordinates = $this->coordinates->forget($key);

        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    public function pointInPolygon(Contracts\CoordinatesInterface $coordinate): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (!$this->bounds->pointInBounds($coordinate)) {
            return false;
        }

        if ($this->pointOnVertex($coordinate)) {
            return true;
        }

        if ($this->pointOnBoundary($coordinate)) {
            return true;
        }

        return $this->pointOnIntersections($coordinate);
    }

    public function pointOnBoundary(Contracts\CoordinatesInterface $coordinate): bool
    {
        $total = $this->count();
        for ($i = 1; $i <= $total; $i++) {
            $currentVertex = $this->get($i - 1);
            $nextVertex = $this->get($i);

            if (is_null($nextVertex)) {
                $nextVertex = $this->get(0);
            }

            // Check if coordinate is on a horizontal boundary
            if (bccomp(
                (string)$currentVertex->getLatitude(),
                (string)$nextVertex->getLatitude(),
                $this->getPrecision()
            ) === 0
                && bccomp(
                    (string)$currentVertex->getLatitude(),
                    (string)$coordinate->getLatitude(),
                    $this->getPrecision()
                ) === 0
                && bccomp(
                    $coordinate->getLongitude(),
                    min($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) === 1
                && bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) === -1
            ) {
                return true;
            }

            // Check if coordinate is on a boundary
            if (bccomp(
                $coordinate->getLatitude(),
                min($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                $this->getPrecision()
            ) === 1
                && bccomp(
                    $coordinate->getLatitude(),
                    max($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) <= 0
                && bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) <= 0
                && bccomp(
                    $currentVertex->getLatitude(),
                    $nextVertex->getLatitude(),
                    $this->getPrecision()
                ) !== 0
            ) {
                $xinters = ($coordinate->getLatitude() - $currentVertex->getLatitude())
                    * ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                    / ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                    + $currentVertex->getLongitude();

                if (bccomp(
                    $xinters,
                    $coordinate->getLongitude(),
                    $this->getPrecision()
                ) === 0
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function pointOnVertex(Contracts\CoordinatesInterface $coordinate): bool
    {
        foreach ($this->coordinates as $vertexCoordinate) {
            if (bccomp(
                $vertexCoordinate->getLatitude(),
                $coordinate->getLatitude(),
                $this->getPrecision()
            ) === 0 &&
                bccomp(
                    $vertexCoordinate->getLongitude(),
                    $coordinate->getLongitude(),
                    $this->getPrecision()
                ) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    protected function pointOnIntersections(Contracts\CoordinatesInterface $coordinate): bool
    {
        $total = $this->count();
        $intersections = 0;
        for ($i = 1; $i < $total; $i++) {
            $currentVertex = $this->get($i - 1);
            $nextVertex = $this->get($i);

            if (bccomp(
                $coordinate->getLatitude(),
                min($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                $this->getPrecision()
            ) === 1
                && bccomp(
                    $coordinate->getLatitude(),
                    max($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) <= 0
                && bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) <= 0
                && bccomp(
                    $currentVertex->getLatitude(),
                    $nextVertex->getLatitude(),
                    $this->getPrecision()
                ) !== 0
            ) {
                $xinters = ($coordinate->getLatitude() - $currentVertex->getLatitude())
                    * ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                    / ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                    + $currentVertex->getLongitude();

                if (bccomp(
                    $coordinate->getLongitude(),
                    $xinters,
                    $this->getPrecision()
                ) <= 0
                    || bccomp(
                        $currentVertex->getLongitude(),
                        $nextVertex->getLongitude(),
                        $this->getPrecision()
                    ) === 0
                ) {
                    $intersections++;
                }
            }
        }

        return ($intersections % 2) != 0;
    }

    //
    //
    //

    public function isEmpty(): bool
    {
        return $this->count() < 1;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->coordinates->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->coordinates->jsonSerialize();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->coordinates->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->coordinates->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->coordinates->offsetSet($offset, $value);
        $this->bounds->setPolygon($this);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->coordinates->offsetUnset($offset);
        $this->bounds->setPolygon($this);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->coordinates->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return $this->coordinates->getIterator();
    }
}
