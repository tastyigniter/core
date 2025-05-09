<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite\Provider;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model\Distance;
use Igniter\Flame\Geolite\Model\Location;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

class GoogleProvider extends AbstractProvider
{
    public function __construct(HttpClient $client, protected array $config)
    {
        $this->httpClient = $client;
    }

    public function getName(): string
    {
        return 'Google Maps';
    }

    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        $endpoint = array_get($this->config, 'endpoints.geocode');
        $url = $this->prependGeocodeQuery($query, sprintf($endpoint,
            rawurlencode($query->getText()),
        ));

        $result = [];

        try {
            $result = $this->cacheCallback($url, fn(): array => $this->hydrateResponse($this->requestGeocodingUrl($url, $query), $query->getLimit()));
        } catch (Throwable $throwable) {
            $this->log(sprintf(
                'Provider "%s" could not geocode address, "%s".',
                $this->getName(), $throwable->getMessage(),
            ));
        }

        return collect($result);
    }

    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $endpoint = array_get($this->config, 'endpoints.reverse');
        $url = $this->prependReverseQuery($query, sprintf($endpoint,
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
        ));

        $result = [];

        try {
            $result = $this->cacheCallback($url, fn(): array => $this->hydrateResponse($this->requestGeocodingUrl($url, $query), $query->getLimit()));
        } catch (Throwable $throwable) {
            $this->log(sprintf(
                'Provider "%s" could not geocode address, "%s".',
                $this->getName(), $throwable->getMessage(),
            ));
        }

        return collect($result);
    }

    public function distance(DistanceInterface $distance): ?Distance
    {
        $endpoint = array_get($this->config, 'endpoints.distance');
        $url = $this->prependDistanceQuery($distance, sprintf($endpoint,
            $distance->getFrom()->getLongitude(),
            $distance->getFrom()->getLatitude(),
            $distance->getTo()->getLongitude(),
            $distance->getTo()->getLatitude(),
        ));

        try {
            return $this->cacheCallback($url, function() use ($distance, $url): Distance {
                $response = $this->requestDistanceUrl($url, $distance);

                return new Distance(
                    array_get($response, '0.elements.0.distance.value', 0),
                    array_get($response, '0.elements.0.duration.value', 0),
                );
            });
        } catch (Throwable $throwable) {
            $this->log(sprintf('Provider "%s" could not calculate distance, "%s".',
                $this->getName(), $throwable->getMessage(),
            ));

            return null;
        }
    }

    protected function hydrateResponse(array $response, int $limit): array
    {
        $result = [];
        foreach ($response as $place) {
            $address = new Location($this->getName());

            // set official Google place id
            if (isset($place->place_id)) {
                $address->setValue('id', $place->place_id);
            }

            if (isset($place->geometry)) {
                $this->parseCoordinates($address, $place->geometry);
            }

            if (isset($place->address_components)) {
                $this->parseAddressComponents($address, $place->address_components);
            }

            if (isset($place->formatted_address)) {
                $address->withFormattedAddress($place->formatted_address);
            }

            $result[] = $address;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    protected function requestGeocodingUrl($url, GeoQueryInterface $query): array
    {
        if ($locale = $query->getLocale()) {
            $url = sprintf('%s&language=%s', $url, $locale);
        }

        if ($region = $query->getData('region', array_get($this->config, 'region'))) {
            $url = sprintf('%s&region=%s', $url, $region);
        }

        if ($apiKey = array_get($this->config, 'apiKey')) {
            $url = sprintf('%s&key=%s', $url, $apiKey);
        }

        $response = $this->getHttpClient()->get($url, [
            'timeout' => $query->getData('timeout', 15),
        ]);

        return $this->parseResponse($this->validateResponse($response));
    }

    protected function requestDistanceUrl($url, DistanceInterface $query): array
    {
        if ($apiKey = array_get($this->config, 'apiKey')) {
            $url = sprintf('%s&key=%s', $url, $apiKey);
        }

        $response = $this->getHttpClient()->get($url, [
            'timeout' => $query->getData('timeout', 15),
        ]);

        $this->validateResponse($response);

        return array_get(json_decode($response->getBody()->getContents(), true), 'rows', []);
    }

    //
    //
    //

    protected function validateResponse(ResponseInterface $response): ResponseInterface
    {
        $json = json_decode($response->getBody()->getContents(), false);

        // API error
        if (!$json) {
            throw new GeoliteException('The geocoder server returned an empty or invalid response.');
        }

        if ($json->status === 'REQUEST_DENIED') {
            throw new GeoliteException(sprintf(
                'API access denied. Message: %s', $json->error_message ?? 'empty error message',
            ));
        }

        // you are over your quota
        if ($json->status === 'OVER_QUERY_LIMIT') {
            throw new GeoliteException(sprintf(
                'Daily quota exceeded. Message: %s', $json->error_message ?? 'empty error message',
            ));
        }

        return $response;
    }

    /**
     * Decode the response content and validate it to make sure it does not have any errors.
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $json = json_decode($response->getBody()->getContents(), false);

        $response = $json->results ?? $json->rows ?? [];
        if (!count($response) || $json->status !== 'OK') {
            throw new GeoliteException($json->error_message ?? 'empty error message');
        }

        return $response;
    }

    protected function prependGeocodeQuery(GeoQueryInterface $query, string $url): string
    {
        if (!is_null($bounds = $query->getBounds())) {
            $url .= sprintf('&bounds=%s,%s|%s,%s',
                $bounds->getSouth(), $bounds->getWest(),
                $bounds->getNorth(), $bounds->getEast(),
            );
        }

        if ($components = $query->getData('components')) {
            $url .= sprintf('&components=%s',
                urlencode($this->serializeComponents($components)),
            );
        }

        return $url;
    }

    protected function prependReverseQuery(GeoQueryInterface $query, string $url): string
    {
        if ($locationType = $query->getData('location_type')) {
            $url .= '&location_type='.urlencode((string) $locationType);
        }

        if ($resultType = $query->getData('result_type')) {
            $url .= '&result_type='.urlencode((string) $resultType);
        }

        return $url;
    }

    protected function prependDistanceQuery(DistanceInterface $distance, string $url): string
    {
        if ($mode = $distance->getData('mode')) {
            $url .= '&mode='.urlencode((string) $mode);
        }

        if ($region = $distance->getData('region', array_get($this->config, 'region'))) {
            $url .= '&region='.urlencode((string) $region);
        }

        if ($language = $distance->getData('language', array_get($this->config, 'locale'))) {
            $url .= '&language='.urlencode((string) $language);
        }

        $units = $distance->getUnit();

        if (!empty($units)) {
            $url .= '&units='.urlencode($units);
        }

        if ($avoid = $distance->getData('avoid')) {
            $url .= '&avoid='.urlencode((string) $avoid);
        }

        if ($departureTime = $distance->getData('departure_time')) {
            $url .= '&departure_time='.urlencode((string)$departureTime);
        }

        if ($arrivalTime = $distance->getData('arrival_time')) {
            $url .= '&arrival_time='.urlencode((string)$arrivalTime);
        }

        return $url;
    }

    protected function parseCoordinates(Location $address, stdClass $geometry)
    {
        $coordinates = $geometry->location;
        $address->setCoordinates($coordinates->lat, $coordinates->lng);

        if (isset($geometry->bounds)) {
            $address->setBounds(
                $geometry->bounds->southwest->lat,
                $geometry->bounds->southwest->lng,
                $geometry->bounds->northeast->lat,
                $geometry->bounds->northeast->lng,
            );
        } elseif (isset($geometry->viewport)) {
            $address->setBounds(
                $geometry->viewport->southwest->lat,
                $geometry->viewport->southwest->lng,
                $geometry->viewport->northeast->lat,
                $geometry->viewport->northeast->lng,
            );
        } elseif ($geometry->location_type === 'ROOFTOP') {
            // Fake bounds
            $address->setBounds(
                $coordinates->lat,
                $coordinates->lng,
                $coordinates->lat,
                $coordinates->lng,
            );
        }
    }

    protected function parseAddressComponents(Location $address, array $components)
    {
        foreach ($components as $component) {
            foreach ($component->types as $type) {
                $this->parseAddressComponent($address, $type, $component);
            }
        }
    }

    protected function parseAddressComponent(Location $address, string $type, stdClass $component): Location
    {
        switch ($type) {
            case 'postal_code':
                return $address->setPostalCode($component->long_name);
            case 'locality':
            case 'postal_town':
                return $address->setLocality($component->long_name);
            case 'administrative_area_level_1':
            case 'administrative_area_level_2':
            case 'administrative_area_level_3':
            case 'administrative_area_level_4':
            case 'administrative_area_level_5':
                return $address->addAdminLevel(
                    (int)substr($type, -1),
                    $component->long_name,
                    $component->short_name,
                );
            case 'sublocality_level_1':
            case 'sublocality_level_2':
            case 'sublocality_level_3':
            case 'sublocality_level_4':
            case 'sublocality_level_5':
                $subLocalityLevel = $address->getValue('subLocalityLevel', []);
                $subLocalityLevel[] = [
                    'level' => (int)substr($type, -1),
                    'name' => $component->long_name,
                    'code' => $component->short_name,
                ];

                return $address->setValue('subLocalityLevel', $subLocalityLevel);
            case 'country':
                $address->setCountryName($component->long_name);

                return $address->setCountryCode($component->short_name);
            case 'street_number':
                return $address->setStreetNumber($component->long_name);
            case 'route':
                return $address->setStreetName($component->long_name);
            case 'sublocality':
                return $address->setSubLocality($component->long_name);
            case 'street_address':
            case 'intersection':
            case 'political':
            case 'colloquial_area':
            case 'ward':
            case 'neighborhood':
            case 'premise':
            case 'subpremise':
            case 'natural_feature':
            case 'airport':
            case 'park':
            case 'point_of_interest':
            case 'establishment':
                return $address->setValue($type, $component->long_name);
            default:
        }

        return $address;
    }

    protected function serializeComponents(string|array $components): string
    {
        if (is_string($components)) {
            return $components;
        }

        return implode('|', array_map(fn($name, $value): string => sprintf('%s:%s', $name, $value), array_keys($components), $components));
    }
}
