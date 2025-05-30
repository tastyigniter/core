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

class NominatimProvider extends AbstractProvider
{
    public function __construct(HttpClient $client, array $config)
    {
        $this->httpClient = $client;
        $this->config = $config;
    }

    /**
     * Returns the provider name.
     */
    public function getName(): string
    {
        return 'Open street maps';
    }

    /**
     * Handle the geocoder request.
     */
    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        $url = sprintf(
            array_get($this->config, 'endpoints.geocode'),
            urlencode($query->getText()),
            $query->getLimit(),
        );

        $result = [];

        try {
            $result = $this->cacheCallback($url, fn(): array => $this->hydrateResponse(
                $this->requestUrl($url, $query),
            ));
        } catch (Throwable $throwable) {
            $this->log(sprintf(
                'Provider "%s" could not geocode address, "%s".',
                $this->getName(), $throwable->getMessage(),
            ));
        }

        return collect($result);
    }

    /**
     * Handle the reverse geocoding request.
     */
    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $url = sprintf(
            array_get($this->config, 'endpoints.reverse'),
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
            $query->getData('zoom', 18),
        );

        $result = [];

        try {
            $result = $this->cacheCallback($url, fn(): array => $this->hydrateResponse(
                $this->requestUrl($url, $query),
            ));
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
        $url = sprintf($endpoint,
            $distance->getData('mode', 'car'),
            $distance->getFrom()->getLongitude(),
            $distance->getFrom()->getLatitude(),
            $distance->getTo()->getLongitude(),
            $distance->getTo()->getLatitude(),
        );

        try {
            $url .= '?overview=false';

            return $this->cacheCallback($url, function() use ($distance, $url): Distance {
                $response = $this->requestDistanceUrl($url, $distance);
                $route = current($response);

                return new Distance($route->distance ?? 0, $route->duration ?? 0);
            });
        } catch (Throwable $throwable) {
            $this->log(sprintf('Provider "%s" could not calculate distance, "%s".',
                $this->getName(), $throwable->getMessage(),
            ));

            return null;
        }
    }

    protected function requestUrl(string $url, GeoQueryInterface $query): array
    {
        if ($locale = $query->getLocale()) {
            $url = sprintf('%s&accept-language=%s', $url, $locale);
        }

        if ($region = $query->getData('countrycodes', array_get($this->config, 'region'))) {
            $url = sprintf('%s&countrycodes=%s', $url, $region);
        }

        $options['headers']['User-Agent'] = $query->getData('userAgent', request()->userAgent());
        $options['headers']['Referer'] = $query->getData('referer', request()->headers->get('referer'));
        $options['headers']['timeout'] = $query->getData('timeout', 15);

        if (empty($options['headers']['User-Agent'])) {
            throw new GeoliteException('The User-Agent must be set to use the Nominatim provider.');
        }

        $response = $this->getHttpClient()->get($url, $options);

        return $this->parseResponse($response);
    }

    protected function hydrateResponse(array $response): array
    {
        $result = [];
        foreach ($response as $location) {
            $address = new Location($this->getName());

            $this->parseCoordinates($address, $location);

            // set official place id
            if (isset($location->place_id)) {
                $address->setValue('id', $location->place_id);
            }

            $this->parseAddress($address, $location);

            if (isset($location->display_name)) {
                $address->withFormattedAddress($location->display_name);
            }

            $result[] = $address;
        }

        return $result;
    }

    //
    //
    //

    protected function parseResponse(ResponseInterface $response, ?string $returnKey = null): array
    {
        $json = json_decode($response->getBody()->getContents(), false);

        if (empty($json)) {
            throw new GeoliteException(
                'The geocoder server returned an empty or invalid response. Make sure the app region or country is properly set.'
            );
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 401 || $statusCode === 403) {
            throw new GeoliteException(sprintf(
                'API access denied. Message: %s', $json->error_message ?? 'empty error message',
            ));
        }

        if ($statusCode === 429) {
            throw new GeoliteException(sprintf(
                'Daily quota exceeded. Message: %s', $json->error_message ?? 'empty error message',
            ));
        }

        if ($statusCode >= 300) {
            throw new GeoliteException(sprintf(
                'The geocoder server returned [%s] an invalid response for query. Message: %s.',
                $statusCode, $json->error_message ?? 'empty error message',
            ));
        }

        if (isset($json->lat)) {
            $json = [$json];
        }

        return $returnKey ? $json->$returnKey : $json;
    }

    protected function parseCoordinates(Location $address, stdClass $location)
    {
        $address->setCoordinates((float) $location->lat, (float) $location->lon);

        if (isset($location->boundingbox)) {
            [$south, $north, $west, $east] = $location->boundingbox;
            $address->setBounds((float) $south, (float) $west, (float) $north, (float) $east);
        }
    }

    protected function parseAddress(Location $address, stdClass $location)
    {
        foreach (['state', 'county'] as $level => $field) {
            if (isset($location->address->{$field})) {
                $address->addAdminLevel($level + 1, $location->address->{$field}, '');
            }
        }

        if (isset($location->address->postcode)) {
            $address->setPostalCode(current(explode(';', $location->address->postcode)));
        }

        foreach (['city', 'town', 'village', 'hamlet'] as $field) {
            if (isset($location->address->{$field})) {
                $address->setLocality($location->address->{$field});
                break;
            }
        }

        $address->setStreetNumber($location->address->house_number ?? null);
        $address->setStreetName($location->address->road ?? $location->address->pedestrian ?? null);
        $address->setSubLocality($location->address->suburb ?? null);
        $address->setCountryName($location->address->country ?? null);

        $countryCode = $location->address->country_code ?? null;
        $address->setCountryCode($countryCode ? strtoupper($countryCode) : null);
    }

    protected function requestDistanceUrl(string $url, DistanceInterface $distance): array
    {
        if ($apiKey = array_get($this->config, 'apiKey')) {
            $url = sprintf('%s&key=%s', $url, $apiKey);
        }

        $options['User-Agent'] = $distance->getData('userAgent', request()->userAgent());
        $options['Referer'] = $distance->getData('referer', request()->get('referer'));
        $options['timeout'] = $distance->getData('timeout', 15);

        if (empty($options['User-Agent'])) {
            throw new GeoliteException('The User-Agent must be set to use the Nominatim provider.');
        }

        $response = $this->getHttpClient()->get($url, $options);

        return $this->parseResponse($response, 'routes');
    }
}
