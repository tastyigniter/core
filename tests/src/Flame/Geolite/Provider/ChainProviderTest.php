<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite\Provider;

use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeocoderInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Distance;
use Igniter\Flame\Geolite\Provider\ChainProvider;

it('returns empty collection when no providers are available for geocode query', function() {
    $geocoder = mock(GeocoderInterface::class);
    $chainProvider = new ChainProvider($geocoder);
    $query = mock(GeoQueryInterface::class);
    expect($chainProvider->geocodeQuery($query))->toBeEmpty()
        ->and($chainProvider->getName())->toBe('Chain');
});

it('returns result from first provider with non-empty geocode query result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $result = collect(['result']);
    $provider->shouldReceive('geocodeQuery')->with($query)->andReturn($result);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder);
    $chainProvider->addProvider('provider1');

    expect($chainProvider->geocodeQuery($query))->toBe($result);
});

it('returns empty collection when no providers are available for reserve geocode query', function() {
    $geocoder = mock(GeocoderInterface::class);
    $chainProvider = new ChainProvider($geocoder);
    $query = mock(GeoQueryInterface::class);
    expect($chainProvider->reverseQuery($query))->toBeEmpty();
});

it('returns result from first provider with non-empty reverse query result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $result = collect(['result']);
    $provider->shouldReceive('reverseQuery')->with($query)->andReturn($result);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->reverseQuery($query))->toBe($result);
});

it('returns null when no providers return non-null distance result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $distance = mock(DistanceInterface::class);
    $provider->shouldReceive('distance')->with($distance)->andReturn(null);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->distance($distance))->toBeNull();
});

it('returns result from first provider with non-null distance result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $distance = mock(DistanceInterface::class);
    $result = mock(Distance::class);
    $provider->shouldReceive('distance')->with($distance)->andReturn($result);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->distance($distance))->toEqual($result);
});

it('returns null when no providers return non-null places result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $provider->shouldReceive('placesAutocomplete')->with($query)->andReturn(collect());
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->placesAutocomplete($query))->toBeEmpty();
});

it('returns result from first provider with non-null places result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $result = collect(['result']);
    $provider->shouldReceive('placesAutocomplete')->with($query)->andReturn($result);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->placesAutocomplete($query))->toBe($result);
});

it('returns null when no providers return non-null place coordinates result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $coordinates = new Coordinates(0, 0);
    $provider->shouldReceive('getPlaceCoordinates')->with($query)->andReturn($coordinates);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->getPlaceCoordinates($query))
        ->getLatitude()->toBe(0.0)
        ->getLongitude()->toBe(0.0);
});

it('returns result from first provider with non-null place coordinates result', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $query = mock(GeoQueryInterface::class);
    $coordinates = new Coordinates(51.5074, -0.1278);
    $provider->shouldReceive('getPlaceCoordinates')->with($query)->andReturn($coordinates);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->getPlaceCoordinates($query))->toBe($coordinates);
});

it('returns logs from all providers', function() {
    $geocoder = mock(GeocoderInterface::class);
    $provider = mock(AbstractProvider::class);
    $provider->shouldReceive('getLogs')->andReturn(['log1', 'log2']);
    $geocoder->shouldReceive('makeProvider')->with('provider1')->andReturn($provider);
    $chainProvider = new ChainProvider($geocoder, ['provider1' => []]);
    expect($chainProvider->getLogs())->toBe(['log1', 'log2']);
});
