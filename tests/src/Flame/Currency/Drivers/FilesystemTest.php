<?php

namespace Igniter\Tests\Flame\Currency\Drivers;

use Igniter\Flame\Currency\Drivers\Filesystem;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Storage;

it('creates a new currency when it does not exist', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnFalse();
//    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode([]));
    $disk->shouldReceive('put')->withSomeOfArgs('currencies.json')->andReturnTrue();

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->create($params))->toBeTrue();
});

it('does not create a new currency when it already exists', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode(['USD' => $params]));
    $disk->shouldReceive('put')->withSomeOfArgs('currencies.json')->andReturnTrue();

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->create($params))->toBeTrue();
});

it('returns all currencies', function() {
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode([]));

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);
    expect($driver->all())->toBeArray();
});

it('finds an active or inactive currency by code', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 0,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode(['USD' => $params]));

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->find('USD', null)['name'])->toBe('US Dollar');
});

it('finds an active currency by code', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode(['USD' => $params]));

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->find('USD')['name'])->toBe('US Dollar');
});

it('updates a currency by code', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode(['USD' => $params]));
    $disk->shouldReceive('put')->withSomeOfArgs('currencies.json')->andReturnTrue();
    $attributes = [
        'name' => 'US Dollar',
        'symbol' => '$',
        'currency_rate' => 1.1,
    ];

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->update('USD', $attributes))->toBe(1);
});

it('returns zero when updating currency that does not exist', function() {
    $attributes = [
        'name' => 'US Dollar',
        'symbol' => '$',
        'currency_rate' => 1.1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode([]));

    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->update('USD', $attributes))->toBe(0);
});

it('deletes a currency by code', function() {
    $params = [
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'format' => '$1,000.00',
        'currency_rate' => 1,
        'active' => 1,
    ];
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode(['USD' => $params]));
    $disk->shouldReceive('put')->withSomeOfArgs('currencies.json')->andReturnTrue();
    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->delete('USD'))->toBe(1);
});

it('returns zero when deleting currency that does not exist', function() {
    Storage::shouldReceive('disk')->with('local')->andReturn($disk = mock(LocalFilesystemAdapter::class));
    $disk->shouldReceive('exists')->with('currencies.json')->andReturnTrue();
    $disk->shouldReceive('get')->with('currencies.json')->andReturn(json_encode([]));
    $driver = new Filesystem(['disk' => 'local', 'path' => 'currencies.json']);

    expect($driver->delete('USD'))->toBe(0);
});
