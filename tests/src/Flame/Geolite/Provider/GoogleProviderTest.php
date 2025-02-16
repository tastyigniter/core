<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite\Provider;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\GeoQuery;
use Igniter\Flame\Geolite\Model\Bounds;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Distance;
use Igniter\Flame\Geolite\Model\Location;
use Igniter\Flame\Geolite\Provider\GoogleProvider;
use Psr\Http\Message\ResponseInterface;

beforeEach(function() {
    config(['igniter-geocoder.cache.duration' => 0]);
    $this->geocoderResponse = [
        'place_id' => '123',
        'address_components' => [
            [
                'long_name' => '1234',
                'short_name' => '1234',
                'types' => ['street_number'],
            ],
            [
                'long_name' => 'Test Street',
                'short_name' => 'Test St',
                'types' => ['route'],
            ],
            [
                'long_name' => 'Test Estate',
                'short_name' => 'Test Estate',
                'types' => ['establishment'],
            ],
            [
                'long_name' => 'Test Locality',
                'short_name' => 'Test Locality',
                'types' => ['locality', 'political'],
            ],
            [
                'long_name' => 'Test SubLocality',
                'short_name' => 'Test SubLocality',
                'types' => ['sublocality', 'political'],
            ],
            [
                'long_name' => 'Test SubLocality 1',
                'short_name' => 'Test SubLocality 1',
                'types' => ['sublocality_level_1', 'political'],
            ],
            [
                'long_name' => 'Test Admin Area 2',
                'short_name' => 'Test Admin Area 2',
                'types' => ['administrative_area_level_2', 'political'],
            ],
            [
                'long_name' => 'Test Admin Area 1',
                'short_name' => 'TA1',
                'types' => ['administrative_area_level_1', 'political'],
            ],
            [
                'long_name' => 'Test Country',
                'short_name' => 'TC',
                'types' => ['country', 'political'],
            ],
            [
                'long_name' => '12345',
                'short_name' => '12345',
                'types' => ['postal_code'],
            ],
            [
                'long_name' => 'Invalid Type',
                'short_name' => 'Invalid Type',
                'types' => ['invalid_type'],
            ],
        ],
        'formatted_address' => '1234 Test Street, Test Locality, Test Admin Area 2, TA1 TC 12345',
        'geometry' => [
            'location' => [
                'lat' => 1,
                'lng' => 2,
            ],
            'location_type' => 'ROOFTOP',
            'viewport' => [
                'northeast' => ['lat' => 1.1, 'lng' => 2.1],
                'southwest' => ['lat' => 0.9, 'lng' => 1.9],
            ],
        ],
        'types' => ['street_address'],
    ];
});

it('returns empty collection when geocode query fails', function() {
    $client = mock(HttpClient::class);
    $client->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com']]);
    $query = new GeoQuery('test');
    $query->withBounds(new Bounds(1, 2, 3, 4));
    $query->withData('components', ['country' => 'us']);
    expect($provider->geocodeQuery($query))->toBeEmpty()
        ->and($provider->getLogs()[0])->toContain('Error');
});

it('returns geocode results when query is successful', function() {
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'status' => 'OK',
        'results' => [$this->geocoderResponse],
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com'], 'apiKey' => 'test']);
    $query = new GeoQuery('test');
    $query->withLimit(1);
    $query->withLocale('fr');
    $query->withData('region', 'us');
    $query->withData('components', 'country:us');

    $result = $provider->geocodeQuery($query);
    expect($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf(Location::class);
});

it('returns cached geocode results when query was previously geocoded', function() {
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->twice()->andReturn(json_encode([
        'status' => 'OK',
        'results' => [$this->geocoderResponse],
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com'], 'apiKey' => 'test']);
    $provider->setCacheLifetime(1234);
    $query = new GeoQuery('test');
    $query->withLimit(1);
    $query->withLocale('fr');
    $query->withData('region', 'us');
    $query->withData('components', 'country:us');

    expect($provider->geocodeQuery($query))->toEqual($provider->geocodeQuery($query));
    $provider->forgetCache();
});

it('returns geocode results with geometry bounds when query is successful', function() {
    $this->geocoderResponse['geometry']['bounds'] = [
        'northeast' => ['lat' => 1.1, 'lng' => 2.1],
        'southwest' => ['lat' => 0.9, 'lng' => 1.9],
    ];
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'status' => 'OK',
        'results' => [$this->geocoderResponse],
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com'], 'apiKey' => 'test']);
    $query = new GeoQuery('test');
    expect($provider->geocodeQuery($query))->toHaveCount(1)
        ->and($provider->geocodeQuery($query)->first())->toBeInstanceOf(Location::class);
});

it('returns geocode results with rooftop geometry location type when query is successful', function() {
    $this->geocoderResponse['geometry']['location_type'] = 'ROOFTOP';
    unset($this->geocoderResponse['geometry']['viewport']);
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'status' => 'OK',
        'results' => [$this->geocoderResponse],
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com'], 'apiKey' => 'test']);
    $query = new GeoQuery('test');
    expect($provider->geocodeQuery($query))->toHaveCount(1);
});

it('returns empty collection when reverse query fails', function() {
    $client = mock(HttpClient::class);
    $client->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $provider = new GoogleProvider($client, ['endpoints' => ['reverse' => 'http://example.com']]);
    $query = new GeoQuery(new Coordinates(1, 2));
    $query->withData('location_type', 'ROOFTOP');
    $query->withData('result_type', 'route');
    expect($provider->reverseQuery($query))->toBeEmpty()
        ->and($provider->getLogs()[0])->toContain('Error');
});

it('returns reverse geocode results when query is successful', function() {
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'status' => 'OK',
        'results' => [$this->geocoderResponse],
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['reverse' => 'http://example.com'], 'apiKey' => 'test']);
    $query = new GeoQuery('test');
    $query->withCoordinates(new Coordinates(1, 2));
    $query->withLimit(1);

    $result = $provider->reverseQuery($query);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toBeInstanceOf(Location::class);
});

it('returns null when distance query fails', function() {
    $client = mock(HttpClient::class);
    $client->shouldReceive('get')->andThrow(new GeoliteException('Error'));
    $provider = new GoogleProvider($client, ['endpoints' => ['distance' => 'http://example.com']]);
    $distance = new \Igniter\Flame\Geolite\Distance;
    $distance->in('mi');
    $distance->setFrom(new Coordinates(1, 2));
    $distance->setTo(new Coordinates(1, 2));
    $distance->withData('mode', 'driving');
    $distance->withData('region', 'gb');
    $distance->withData('language', 'en');
    $distance->withData('avoid', 'tolls');
    $distance->withData('departure_time', time());
    $distance->withData('arrival_time', (string)time());
    expect($provider->distance($distance))->toBeNull()
        ->and($provider->getLogs()[0])->toContain('Error');
});

it('returns distance result when query is successful', function() {
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode([
        'rows' => [
            [
                'elements' => [
                    [
                        'distance' => ['text' => '1 mi', 'value' => 1609],
                        'duration' => ['text' => '1 min', 'value' => 60],
                        'status' => 'OK',
                    ],
                ],
            ],
        ],
        'status' => 'OK',
    ]));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['distance' => 'http://example.com'], 'apiKey' => 'test']);
    $distance = new \Igniter\Flame\Geolite\Distance;
    $distance->in('mi');
    $distance->setFrom(new Coordinates(1, 2));
    $distance->setTo(new Coordinates(1, 2));

    $result = $provider->distance($distance);

    expect($result)->toBeInstanceOf(Distance::class)
        ->and($result->getDistance())->toBe(1609.0)
        ->and($result->getDuration())->toBe(60)
        ->and(round($result->formatDistance(), 2))->toBe(1.0)
        ->and($result->formatDuration())->toBe('1 minute before');
});

it('throws exception when geocoder server returns empty response', function($responseData, $exceptionMessage) {
    $client = mock(HttpClient::class);
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($responseData));
    $client->shouldReceive('get')->andReturn($response);
    $provider = new GoogleProvider($client, ['endpoints' => ['geocode' => 'http://example.com'], 'apiKey' => 'test']);
    $query = new GeoQuery('test');
    $query->withLimit(1);
    $query->withLocale('fr');
    $query->withData('region', 'us');

    $provider->geocodeQuery($query);

    expect($provider->getLogs()[0])->toContain($exceptionMessage);
})->with([
    'empty response' => [
        [], 'The geocoder server returned an empty or invalid response.',
    ],
    'request denied' => [
        [
            'status' => 'REQUEST_DENIED',
            'error_message' => 'Access denied',
        ],
        'API access denied. Message: Access denied',
    ],
    'over query limit' => [
        [
            'status' => 'OVER_QUERY_LIMIT',
            'error_message' => 'Query limit exceeded',
        ],
        'Daily quota exceeded. Message: Query limit exceeded',
    ],
    'invalid response' => [
        [
            'status' => 'INVALID_REQUEST',
            'error_message' => 'Invalid request',
        ],
        'Invalid request',
    ],
]);
