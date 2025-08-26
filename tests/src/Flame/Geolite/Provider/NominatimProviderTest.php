<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite\Provider;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Distance;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\GeoQuery;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Location;
use Igniter\Flame\Geolite\Place;
use Igniter\Flame\Geolite\Provider\NominatimProvider;
use Psr\Http\Message\ResponseInterface;

beforeEach(function() {
    config(['igniter-geocoder.cache.duration' => 0]);
    $this->httpClient = mock(HttpClient::class);
    $this->provider = new NominatimProvider($this->httpClient, [
        'endpoints' => [
            'geocode' => 'http://localhost/geocode?q=%s&limit=%d',
            'reverse' => 'http://localhost/reverse?lat=%s&lon=%s',
            'distance' => 'http://localhost/distance?from=%s&to=%s',
        ],
        'apiKey' => 'test-api-key',
    ]);
    $this->geocoderResponse = [
        'place_id' => 100149,
        'licence' => 'Data © OpenStreetMap contributors, ODbL 1.0. https://osm.org/copyright',
        'osm_type' => 'node',
        'osm_id' => '12345',
        'boundingbox' => [1, 2, 3, 4],
        'lat' => 1,
        'lon' => 3,
        'display_name' => '1234 Test Street, Test Locality, Test Admin Area 2, TA1 TC 12345',
        'class' => 'place',
        'type' => 'city',
        'importance' => 0.9654895765402,
        'icon' => 'https://nominatim.openstreetmap.org/images/mapicons/poi_place_city.p.20.png',
        'address' => [
            'city' => 'Test Locality',
            'state_district' => 'Test Admin Area 1',
            'state' => 'Test Admin Area 2',
            'ISO3166-2-lvl4' => 'GB-ENG',
            'postcode' => '12345',
            'country' => 'United Kingdom',
            'country_code' => 'gb',
        ],
        'extratags' => [
            'capital' => 'yes',
            'website' => 'http://www.london.gov.uk',
            'wikidata' => 'Q84',
            'wikipedia' => 'en:London',
            'population' => '8416535',
        ],
    ];
});

it('returns empty collection when geocode query fails', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $query = new GeoQuery('test address');
    $query->withLocale('en');
    $query->withData('countrycodes', 'gb');

    $this->provider->resetLogs();

    expect($this->provider->geocodeQuery($query))->toBeEmpty()
        ->and($this->provider->getLogs()[0])->toContain('Error');
});

it('returns geocode results when query is successful', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($this->geocoderResponse));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');

    $result = $this->provider->geocodeQuery($query);
    expect($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf(Location::class);
});

it('returns cached geocode results when query was previously geocoded', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->once()->andReturn(json_encode($this->geocoderResponse));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');
    $this->provider->setCacheLifetime(1234);

    expect($this->provider->geocodeQuery($query))->toEqual($this->provider->geocodeQuery($query));
    $this->provider->forgetCache();
});

it('returns empty collection when reverse query fails', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $query = new GeoQuery('test address');
    $query->withCoordinates(new Coordinates(1, 3));

    expect($this->provider->reverseQuery($query))->toBeEmpty()
        ->and($this->provider->getLogs()[0])->toContain('Error');
});

it('returns reverse geocode results when query is successful', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($this->geocoderResponse));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');
    $query->withCoordinates(new Coordinates(1, 3));

    $result = $this->provider->reverseQuery($query);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf(Location::class);
});

it('throws exception when user agent is not set for geocode query', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $query = new GeoQuery('test address');
    $query->withData('userAgent', '');

    expect($this->provider->geocodeQuery($query))->toBeEmpty()
        ->and($this->provider->getLogs()[0])->toContain('The User-Agent must be set to use the Nominatim provider.');
});

it('returns null when distance query fails', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $distance = new Distance;
    $distance->in('mi');
    $distance->setFrom(new Coordinates(1, 2));
    $distance->setTo(new Coordinates(1, 2));

    expect($this->provider->distance($distance))->toBeNull()
        ->and($this->provider->getLogs()[0])->toContain('Error');
});

it('returns distance result when query is successful', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'code' => 'Ok',
        'routes' => [
            [
                'geometry' => 'xgmiNss{lE??',
                'legs' => [
                    [
                        'steps' => [],
                        'summary' => '',
                        'weight' => 12,
                        'duration' => 123,
                        'distance' => 123,
                    ],
                ],
                'weight_name' => 'routability',
                'weight' => 12,
                'duration' => 123,
                'distance' => 123,
            ],
        ],
        'waypoints' => [
            [
                'hint' => str_random(),
                'distance' => 194048.204933684,
                'name' => 'Main Street',
                'location' => [1, 2],
            ],
            [
                'hint' => str_random(),
                'distance' => 194048.204933684,
                'name' => 'Main Street',
                'location' => [1, 2],
            ],
        ],
    ]));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $distance = new Distance;
    $distance->in('mi');
    $distance->setFrom(new Coordinates(1, 2));
    $distance->setTo(new Coordinates(1, 2));

    $result = $this->provider->distance($distance);

    expect($result)->toBeInstanceOf(\Igniter\Flame\Geolite\Model\Distance::class)
        ->and(round($result->getDistance(), 2))->toBe(123.0)
        ->and($result->getDuration())->toBe(123.0)
        ->and(round($result->formatDistance(), 2))->toBe(0.08)
        ->and($result->formatDuration())->toBe('2 minutes before');
});

it('throws exception when user agent is not set for distance query', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $distance = new Distance;
    $distance->in('mi');
    $distance->setFrom(new Coordinates(1, 2));
    $distance->setTo(new Coordinates(1, 2));
    $distance->withData('userAgent', '');

    expect($this->provider->distance($distance))->toBeEmpty()
        ->and($this->provider->getLogs()[0])->toContain('The User-Agent must be set to use the Nominatim provider.');
});

it('returns empty collection when places autocomplete query fails', function() {
    $this->httpClient->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $query = new GeoQuery('test address');
    expect(fn() => $this->provider->placesAutocomplete($query))->toThrow(GeoliteException::class)
        ->and($this->provider->getLogs()[0])->toContain('Error');
});

it('returns places autocomplete results when query is successful', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        [
            'place_id' => 'place_id_123',
            'name' => 'Test Place',
            'display_name' => 'Test Place, Test Locality, Test Admin Area 2, TA1 TC 12345',
            'osm_type' => 'node',
            'osm_id' => '12345',
            'category' => 'boundary',
            'lat' => 51.5073219,
            'lon' => -0.1276474,
        ],
    ]));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');
    $query->withData('countrycodes', 'gb');

    $result = $this->provider->placesAutocomplete($query);
    expect($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf(Place::class)
        ->and($result->first()->getPlaceId())->toBe('place_id_123')
        ->and($result->first()->getTitle())->toBe('Test Place')
        ->and($result->first()->getDescription())->toBe('Test Place, Test Locality, Test Admin Area 2, TA1 TC 12345')
        ->and($result->first()->getProvider())->toBe('nominatim')
        ->and($result->first()->getData('latitude'))->toBe(51.5073219)
        ->and($result->first()->getData('longitude'))->toBe(-0.1276474)
        ->and($result->first()->getData('non-exists'))->toBeNull()
        ->and($result->first()->toArray())->toHaveKeys(['placeId', 'title', 'description', 'provider', 'data']);
});

it('returns empty coordinates when places coordinates query fails', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(500);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode(['error' => 'Server Error']));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');
    expect(fn() => $this->provider->getPlaceCoordinates($query))->toThrow(GeoliteException::class)
        ->and($this->provider->getLogs()[0])->toContain('Error');
});

it('returns place coordinates when query is successful', function() {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([]));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');

    $result = $this->provider->getPlaceCoordinates($query);
    expect($result)->toBeInstanceOf(Coordinates::class)
        ->and($result->getLatitude())->toBe(0.0)
        ->and($result->getLongitude())->toBe(0.0);
});

it('throws exception when geocoder server returns empty response', function($responseData, $statusCode, $exceptionMessage) {
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn($statusCode);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($responseData));
    $this->httpClient->shouldReceive('get')->andReturn($response);
    $query = new GeoQuery('test address');

    expect($this->provider->geocodeQuery($query))->toBeEmpty()
        ->and($this->provider->getLogs()[0])->toContain($exceptionMessage);
})->with([
    'empty response' => [
        [], 200, 'The geocoder server returned an empty or invalid response.',
    ],
    'request denied' => [
        ['error_message' => 'Unauthorized'], 401, 'API access denied. Message: Unauthorized',
    ],
    'over query limit' => [
        ['error_message' => 'Over query limit'], 429, 'Daily quota exceeded. Message: Over query limit',
    ],
    'invalid response' => [
        ['error_message' => 'Invalid Response'],
        300,
        'The geocoder server returned [300] an invalid response for query. Message: Invalid Response.',
    ],
]);
