<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Facades\Geocoder;
use Igniter\Flame\Geolite\GeoQuery;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Distance;
use Igniter\Flame\Geolite\Provider\ChainProvider;
use Igniter\Flame\Geolite\Provider\GoogleProvider;
use Igniter\Flame\Geolite\Provider\NominatimProvider;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

beforeEach(function() {
    config(['igniter-geocoder.cache.duration' => 0]);
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

it('throws an exception when query text is empty', function() {
    expect(fn() => new GeoQuery(''))->toThrow('Geocode query cannot be empty');

    $query = GeoQuery::create('test address');
    $query->withData('key', 'value');
    expect($query->getAllData())->toBe(['key' => 'value'])
        ->and((string)$query)->toContain('GeoQuery: ', 'key', 'value');
});

it('geocodes an address with limit and locale', function() {
    app()->instance('geocoder.client', $httpClient = mock(HttpClient::class));
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($this->geocoderResponse));
    $httpClient->shouldReceive('get')->andReturn($response);
    $address = 'test address';
    $geocoder = resolve('geocoder');
    $geocoder->limit(5)->locale('en');
    expect($geocoder->geocode($address))->toBeInstanceOf(Collection::class);

    $query = new GeoQuery($address);
    $query->withLimit(0);

    expect(Geocoder::geocodeQuery($query))->toBeInstanceOf(Collection::class);
});

it('reverses geocodes coordinates with limit and locale', function() {
    app()->instance('geocoder.client', $httpClient = mock(HttpClient::class));
    $response = mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody->getContents')->andReturn(json_encode($this->geocoderResponse));
    $httpClient->shouldReceive('get')->andReturn($response);
    $geocoder = resolve('geocoder');
    $geocoder->limit(5)->locale('en');
    expect($geocoder->reverse(37.4224764, -122.0842499))->toBeInstanceOf(Collection::class);

    $query = new GeoQuery(new Coordinates(37.4224764, -122.0842499));
    $query->withLimit(0);

    expect($geocoder->reverseQuery($query))->toBeInstanceOf(Collection::class);
});

it('creates provider correctly', function() {
    $geocoder = resolve('geocoder');

    expect(fn() => $geocoder->using('unsupported'))->toThrow(InvalidArgumentException::class)
        ->and($geocoder->using('chain'))->toBeInstanceOf(ChainProvider::class)
        ->and($geocoder->using('nominatim'))->toBeInstanceOf(NominatimProvider::class)
        ->and($geocoder->using('google'))->toBeInstanceOf(GoogleProvider::class);
});

it('creates a custom provider', function() {
    $geocoder = resolve('geocoder');
    $geocoder->extend('custom', fn(): AbstractProvider => new class extends AbstractProvider
    {
        public function getName(): string
        {
            return 'Custom';
        }

        public function geocodeQuery(GeoQueryInterface $query): Collection
        {
            return collect([]);
        }

        public function reverseQuery(GeoQueryInterface $query): Collection
        {
            return collect([]);
        }

        public function distance(DistanceInterface $distance): ?Distance
        {
            return null;
        }
    });
    $provider = $geocoder->using('custom');
    expect($provider)->toBeInstanceOf(AbstractProvider::class);
});
