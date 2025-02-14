<?php

namespace Igniter\Tests\System\Traits;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\AssetManager;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Events\AssetsBeforePrepareCombinerEvent;
use Igniter\System\Libraries\Assets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

it('combines assets and returns correct URL', function() {
    app()->instance(AssetManager::class, $assetManager = mock(AssetManager::class));
    $assetManager->shouldReceive('makeCollection')->andReturn($assetCollection = mock(AssetCollection::class));
    $assetCollection->shouldReceive('setTargetPath')->andReturnSelf();
    $assetCollection->shouldReceive('getLastModified')->once()->andReturn(time());
    $assets = [
        'style.css',
        'http://example.com/style.css',
        'assets/css/vendor/animate.css',
        'assets/css/vendor/dropzone.css',
    ];

    $combinesAssetsObject = new Assets;
    $result = $combinesAssetsObject->combine('css', $assets);

    expect($result)->toContain('/_assets/');
});

it('combines assets to file', function() {
    app()->instance(AssetManager::class, $assetManager = mock(AssetManager::class));
    $assetManager->shouldReceive('makeCollection')->andReturn($assetCollection = mock(AssetCollection::class));
    $assetCollection->shouldReceive('setTargetPath')->andReturnSelf();
    $assetCollection->shouldReceive('dump')->once()->andReturn('compiled css');
    $fileMock = File::partialMock();
    $fileMock->shouldReceive('makeDirectory')->andReturnTrue();
    $fileMock->shouldReceive('put')->andReturnTrue();
    $combinesAssetsObject = new Assets;

    $assets = ['igniter.tests::/scss/style.scss'];
    $destination = base_path('/style.css');
    $combinesAssetsObject->combineToFile($assets, $destination);
});

it('throws exception when cache key not found', function() {
    $combinesAssetsObject = new Assets;

    expect(fn() => $combinesAssetsObject->combineGetContents('invalid_cache_key'))
        ->toThrow(SystemException::class, sprintf(lang('igniter::system.not_found.combiner'), 'invalid_cache_key'));
});

it('combines assets and returns correct contents', function() {
    File::deleteDirectory(storage_path('/igniter/combiner/data'));
    Cache::put('ti.combiner.assets_cache', base64_encode(serialize([
        'eTag' => 'eTag',
        'type' => 'css',
        'lastMod' => time(),
        'files' => ['igniter.tests::/scss/style.scss'],
    ])));

    $combinesAssetsObject = new Assets;
    $result = $combinesAssetsObject->combineGetContents('assets_cache');

    expect($result->getContent())->toContain('body {');
});

it('builds bundles and returns notes', function() {
    Event::fake();
    app()->instance(AssetManager::class, $assetManager = mock(AssetManager::class));
    $assetManager->shouldReceive('makeCollection')->andReturn($assetCollection = mock(AssetCollection::class));
    $assetCollection->shouldReceive('setTargetPath')->andReturnSelf();
    $assetCollection->shouldReceive('dump')->andReturn('compiled css');
    $fileMock = File::partialMock();
    $fileMock->shouldReceive('makeDirectory')->andReturnTrue();
    $fileMock->shouldReceive('put')->andReturnTrue();

    $theme = resolve(ThemeManager::class)->findTheme('igniter-orange');
    expect((new Assets)->buildBundles($theme))->toContain('app.scss', ' -> /app.css');

    Event::assertDispatched('assets.combiner.afterBuildBundles');
});

it('flashes error when build bundles fails', function() {
    Event::listen(AssetsBeforePrepareCombinerEvent::class, function($event) {
        throw new \Exception('Error');
    });

    $theme = resolve(ThemeManager::class)->findTheme('igniter-orange');

    $combinesAssetsObject = new Assets;
    $combinesAssetsObject->buildBundles($theme);
    $combinesAssetsObject->resetFilters('css');
    $combinesAssetsObject->resetFilters();

    expect(flash()->messages()->first())->message->toBe('Building assets bundle error: Error');
});

it('registers and retrieves bundles correctly', function() {
    $combinesAssetsObject = new Assets;

    $combinesAssetsObject->registerBundle('js', ['igniter.tests::/js/script.js']);
    $combinesAssetsObject->registerBundle('css', ['igniter.tests::/scss/style.scss']);
    $cssFilters = $combinesAssetsObject->getBundles('css');
    $jsFilters = $combinesAssetsObject->getBundles('js');

    expect($combinesAssetsObject->getBundles())->toHaveCount(2)
        ->and($jsFilters)->toHaveKey('igniter.tests::/js/script.min.js')
        ->and($cssFilters)->toHaveKey('igniter.tests::/scss/../css/style.css');
});
