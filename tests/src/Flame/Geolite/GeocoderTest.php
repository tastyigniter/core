<?php

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\GeoQuery;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Distance;
use Igniter\Flame\Geolite\Provider\ChainProvider;
use Igniter\Flame\Geolite\Provider\GoogleProvider;
use Igniter\Flame\Geolite\Provider\NominatimProvider;
use Illuminate\Support\Collection;
use InvalidArgumentException;

it('throws an exception when query text is empty', function() {
    expect(fn() => new GeoQuery(''))->toThrow('Geocode query cannot be empty');

    $query = GeoQuery::create('test address');
    $query->withData('key', 'value');
    expect($query->getAllData())->toBe(['key' => 'value'])
        ->and((string)$query)->toContain('GeoQuery: ', 'key', 'value');
});

it('geocodes an address with limit and locale', function() {
    $address = 'test address';
    $geocoder = resolve('geocoder');
    $geocoder->limit(5)->locale('en');
    expect($geocoder->geocode($address))->toBeInstanceOf(Collection::class);

    $query = new GeoQuery($address);
    $query->withLimit(0);
    expect(\Igniter\Flame\Geolite\Facades\Geocoder::geocodeQuery($query))->toBeInstanceOf(Collection::class);
});

it('reverses geocodes coordinates with limit and locale', function() {
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
    $geocoder->extend('custom', function() {
        return new class extends AbstractProvider
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
        };
    });
    $provider = $geocoder->using('custom');
    expect($provider)->toBeInstanceOf(AbstractProvider::class);
});
