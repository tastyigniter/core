<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\AssetCache;
use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Cache\CacheInterface;
use Igniter\Flame\Assetic\Filter\CssImportFilter;

it('loads asset from cache if available', function() {
    $cssImportFilter = new CssImportFilter;
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/source/root');
    $asset->shouldReceive('getSourcePath')->andReturn('source_path.css');
    $asset->shouldReceive('getTargetPath')->andReturn('target_path.css');
    $asset->shouldReceive('getLastModified')->andReturn(123456);
    $asset->shouldReceive('getFilters')->andReturn([$cssImportFilter]);
    $asset->shouldReceive('getValues')->andReturn([]);
    $asset->shouldReceive('setContent')->twice();
    $cache = mock(CacheInterface::class);
    $cacheKey = md5('/source/rootsource_path.csstarget_path.css123456'.serialize($cssImportFilter).'load');
    $cache->shouldReceive('has')->once()->with($cacheKey)->andReturnTrue();
    $cache->shouldReceive('get')->once()->with($cacheKey)->andReturn('cached_content');
    $assetCache = new AssetCache($asset, $cache);

    $assetCache->load();
    $assetCache->setContent('cached_content');

    expect($assetCache->getSourceRoot())->toBe('/source/root')
        ->and($assetCache->getSourcePath())->toBe('source_path.css');
});

it('loads asset and caches it if not available in cache', function() {
    $cssImportFilter = new CssImportFilter;
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('ensureFilter')->twice();
    $asset->shouldReceive('getSourceRoot')->andReturn('/source/root');
    $asset->shouldReceive('getSourcePath')->andReturn('source_path.css');
    $asset->shouldReceive('getTargetPath')->andReturn('target_path.css');
    $asset->shouldReceive('getLastModified')->andReturn(123456);
    $asset->shouldReceive('getFilters')->andReturn([]);
    $asset->shouldReceive('getValues')->andReturn(['foo' => 'bar']);
    $asset->shouldReceive('getContent')->twice()->andReturn('new_content');
    $asset->shouldReceive('load')->once();
    $asset->shouldReceive('clearFilters')->once();
    $cache = mock(CacheInterface::class);
    $cacheKey = md5('/source/rootsource_path.csstarget_path.css123456'.serialize(['foo' => 'bar']).'load');
    $cache->shouldReceive('has')->once()->with($cacheKey)->andReturnFalse();
    $cache->shouldReceive('set')->once()->with($cacheKey, 'new_content');
    $assetCache = new AssetCache($asset, $cache);

    $assetCache->load($cssImportFilter);

    expect($assetCache->getContent())->toBe('new_content')
        ->and($assetCache->getValues())->toBe(['foo' => 'bar'])
        ->and($assetCache->getFilters())->toBe([])
        ->and($assetCache->clearFilters())->toBeNull()
        ->and($assetCache->getTargetPath())->toBe('target_path.css')
        ->and($assetCache->getLastModified())->toBe(123456)
        ->and($assetCache->ensureFilter($cssImportFilter))->toBeNull();
});

it('dumps asset from cache if available', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/source/root');
    $asset->shouldReceive('getSourcePath')->andReturn('source_path.css');
    $asset->shouldReceive('getTargetPath')->andReturn('target_path.css');
    $asset->shouldReceive('getLastModified')->andReturn(123456);
    $asset->shouldReceive('getFilters')->andReturn([]);
    $asset->shouldReceive('getValues')->andReturn([]);
    $cache = mock(CacheInterface::class);
    $cache->shouldReceive('has')->once()->andReturnTrue();
    $cache->shouldReceive('get')->once()->andReturn('cached_dump');
    $assetCache = new AssetCache($asset, $cache);

    expect($assetCache->dump())->toBe('cached_dump');
});

it('dumps asset and caches it if not available in cache', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/source/root');
    $asset->shouldReceive('getSourcePath')->andReturn('source_path.css');
    $asset->shouldReceive('getTargetPath')->andReturn('target_path.css');
    $asset->shouldReceive('getLastModified')->andReturn(123456);
    $asset->shouldReceive('getFilters')->andReturn([]);
    $asset->shouldReceive('getValues')->andReturn([]);
    $asset->shouldReceive('getVars')->andReturn([]);
    $asset->shouldReceive('setValues')->with(['foo' => 'bar']);
    $asset->shouldReceive('dump')->andReturn('new_dump');
    $asset->shouldReceive('getSourceDirectory')->andReturn('/source');
    $asset->shouldReceive('setTargetPath')->with('/source/root');
    $cache = mock(CacheInterface::class);
    $cache->shouldReceive('has')->once()->andReturnFalse();
    $cache->shouldReceive('set')->once()->andReturn();
    $assetCache = new AssetCache($asset, $cache);

    expect($assetCache->dump())->toBe('new_dump')
        ->and($assetCache->getSourceDirectory())->toBe('/source')
        ->and($assetCache->setTargetPath('/source/root'))->toBeNull()
        ->and($assetCache->getVars())->toBe([])
        ->and($assetCache->setValues(['foo' => 'bar']))->toBeNull();
});
