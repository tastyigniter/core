<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Cache;

use Igniter\Flame\Assetic\Cache\FilesystemCache;
use Igniter\Flame\Support\Facades\File;
use RuntimeException;

it('checks if cache has a key', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('exists')->with('/path/to/cache/key')->andReturn(true);

    expect($cache->has('key'))->toBeTrue();
});

it('gets value from cache', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('exists')->with('/path/to/cache/key')->andReturn(true);
    File::shouldReceive('get')->with('/path/to/cache/key')->andReturn('cached value');

    expect($cache->get('key'))->toBe('cached value');
});

it('throws exception when getting non-existent key from cache', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('exists')->with('/path/to/cache/key')->andReturn(false);

    expect(fn() => $cache->get('key'))->toThrow(RuntimeException::class);
});

it('sets value in cache', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('isDirectory')->with('/path/to/cache')->andReturn(true);
    File::shouldReceive('put')->once()->with('/path/to/cache/key', 'value')->andReturn(true);

    $cache->set('key', 'value');
});

it('creates directory and sets value in cache', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('isDirectory')->with('/path/to/cache')->andReturn(false);
    File::shouldReceive('makeDirectory')->with('/path/to/cache', 0777, true)->andReturn(true);
    File::shouldReceive('put')->once()->with('/path/to/cache/key', 'value')->andReturn(true);

    $cache->set('key', 'value');
});

it('throws exception when unable to create directory', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('isDirectory')->with('/path/to/cache')->andReturn(false);
    File::shouldReceive('makeDirectory')->with('/path/to/cache', 0777, true)->andReturn(false);

    expect(fn() => $cache->set('key', 'value'))->toThrow(RuntimeException::class);
});

it('throws exception when unable to write file', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('isDirectory')->with('/path/to/cache')->andReturn(true);
    File::shouldReceive('put')->with('/path/to/cache/key', 'value')->andReturn(false);

    expect(fn() => $cache->set('key', 'value'))->toThrow(RuntimeException::class);
});

it('removes value from cache', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('exists')->with('/path/to/cache/key')->andReturn(true);
    File::shouldReceive('delete')->once()->with('/path/to/cache/key')->andReturn(true);

    $cache->remove('key');
});

it('throws exception when unable to remove file', function() {
    $cache = new FilesystemCache('/path/to/cache');
    File::shouldReceive('exists')->with('/path/to/cache/key')->andReturn(true);
    File::shouldReceive('delete')->with('/path/to/cache/key')->andReturn(false);

    expect(fn() => $cache->remove('key'))->toThrow(RuntimeException::class);
});
