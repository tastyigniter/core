<?php

namespace Igniter\Flame\Geolite;

use InvalidArgumentException;

class GeoQuery implements Contracts\GeoQueryInterface
{
    /**
     * The address or text that should be geocoded.
     *
     * @var string
     */
    protected $text;

    /**
     * @var \Igniter\Flame\Geolite\Model\Coordinates
     */
    protected $coordinates;

    /**
     * @var \Igniter\Flame\Geolite\Model\Bounds|null
     */
    protected $bounds;

    /**
     * @var string|null
     */
    protected $locale;

    /**
     * @var int
     */
    protected $limit = Geocoder::DEFAULT_RESULT_LIMIT;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string $text
     */
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

    /**
     * @return self
     */
    public static function create(string $text)
    {
        return new self($text);
    }

    /**
     * @return self
     */
    public function withText(string $text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return self
     */
    public function withBounds(Model\Bounds $bounds)
    {
        $this->bounds = $bounds;

        return $this;
    }

    /**
     * @return self
     */
    public function withLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return self
     */
    public function withLimit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function withData(string $name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return \Igniter\Flame\Geolite\Model\Bounds|null
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getData(string $name, $default = null)
    {
        if (!array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }

    /**
     * @return array
     */
    public function getAllData()
    {
        return $this->data;
    }

    //
    //
    //

    /**
     * @param float $latitude
     * @param float $longitude
     *
     * @return \Igniter\Flame\Geolite\Contracts\GeoQueryInterface
     */
    public static function fromCoordinates($latitude, $longitude)
    {
        return new self(new Model\Coordinates($latitude, $longitude));
    }

    /**
     * @return \Igniter\Flame\Geolite\Contracts\GeoQueryInterface
     */
    public function withCoordinates(Model\Coordinates $coordinates)
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    /**
     * @return \Igniter\Flame\Geolite\Model\Coordinates
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * String for logging. This is also a unique key for the query
     *
     * @return string
     */
    public function __toString()
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
