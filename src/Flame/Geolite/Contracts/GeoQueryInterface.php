<?php

namespace Igniter\Flame\Geolite\Contracts;

interface GeoQueryInterface
{
    /**
     * @return GeoQueryInterface
     */
    public function withLocale(string $locale);

    /**
     * @return GeoQueryInterface
     */
    public function withLimit(int $limit);

    /**
     * @param mixed $value
     *
     * @return GeoQueryInterface
     */
    public function withData(string $name, $value);

    public function getText();

    /**
     * @return \Igniter\Flame\Geolite\Model\Bounds|null
     */
    public function getBounds();

    /**
     * @return string|null
     */
    public function getLocale();

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getData(string $name, $default = null);

    /**
     * @return array
     */
    public function getAllData();

    /**
     * @return \Igniter\Flame\Geolite\Model\Coordinates
     */
    public function getCoordinates();

    /**
     * @return string
     */
    public function __toString();
}
