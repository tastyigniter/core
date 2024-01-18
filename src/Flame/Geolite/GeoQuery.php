<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\BoundsInterface;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Contracts\GeocoderInterface;
use InvalidArgumentException;

class GeoQuery implements Contracts\GeoQueryInterface
{
    /**
     * The address or text that should be geocoded.
     */
    protected ?string $text = null;

    protected ?CoordinatesInterface $coordinates = null;

    protected ?BoundsInterface $bounds = null;

    protected ?string $locale = null;

    protected int $limit = GeocoderInterface::DEFAULT_RESULT_LIMIT;

    protected array $data = [];

    public function __construct($text)
    {
        if ($text instanceof Model\Coordinates) {
            $this->coordinates = $text;
        } elseif (!empty($text) && is_string($text)) {
            $this->text = $text;
        } elseif (empty($text)) {
            throw new InvalidArgumentException('Geocode query cannot be empty');
        }
    }

    public static function create(string $text): self
    {
        return new self($text);
    }

    public function withText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function withBounds(Model\Bounds $bounds): self
    {
        $this->bounds = $bounds;

        return $this;
    }

    public function withLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function withLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function withData(string $name, $value): Contracts\GeoQueryInterface
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getBounds(): ?BoundsInterface
    {
        return $this->bounds;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getData(string $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }

    public function getAllData(): array
    {
        return $this->data;
    }

    //
    //
    //

    public static function fromCoordinates(int|float $latitude, int|float $longitude): self
    {
        return new self(new Model\Coordinates($latitude, $longitude));
    }

    public function withCoordinates(Model\Coordinates $coordinates): self
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getCoordinates(): CoordinatesInterface
    {
        return $this->coordinates;
    }

    /**
     * String for logging. This is also a unique key for the query
     */
    public function __toString(): string
    {
        return sprintf('GeoQuery: %s', json_encode([
            'text' => $this->getText(),
            'bounds' => $this->getBounds() ? $this->getBounds()->toArray() : 'null',
            'coordinates' => $this->getCoordinates()->toArray(),
            'locale' => $this->getLocale(),
            'limit' => $this->getLimit(),
            'data' => $this->getAllData(),
        ]));
    }
}
