<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite\Provider;

use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeocoderInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Model\Distance;
use Illuminate\Support\Collection;

class ChainProvider extends AbstractProvider
{
    public function __construct(protected GeocoderInterface $geocoder, protected array $providers = []) {}

    public function getName(): string
    {
        return 'Chain';
    }

    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        foreach (array_keys($this->providers) as $name) {
            $provider = $this->geocoder->makeProvider($name);
            $result = $provider->geocodeQuery($query);
            if ($result->isNotEmpty()) {
                return $result;
            }
        }

        return new Collection;
    }

    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        foreach (array_keys($this->providers) as $name) {
            $provider = $this->geocoder->makeProvider($name);
            $result = $provider->reverseQuery($query);
            if ($result->isNotEmpty()) {
                return $result;
            }
        }

        return new Collection;
    }

    public function distance(DistanceInterface $distance): ?Distance
    {
        foreach (array_keys($this->providers) as $name) {
            $result = $this->geocoder->makeProvider($name)->distance($distance);
            if (!is_null($result)) {
                return $result;
            }
        }

        return null;
    }

    public function addProvider(string $name, array $config = []): self
    {
        $this->providers[$name] = $config;

        return $this;
    }

    public function getLogs(): array
    {
        $logs = [];
        foreach (array_keys($this->providers) as $name) {
            $provider = $this->geocoder->makeProvider($name);
            $logs[] = $provider->getLogs();
        }

        return array_merge(...$logs);
    }
}
