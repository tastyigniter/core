<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Igniter\Flame\Support\Facades\File;
use Igniter\System\Facades\Assets;
use Igniter\System\Traits\AssetMaker;

it('flushes all assets', function() {
    Assets::shouldReceive('flush')->once();
    $assetMaker = new class
    {
        use AssetMaker;
    };

    $assetMaker->flushAssets();
});

it('returns full URL if file name starts with http', function() {
    $assetMaker = new class
    {
        use AssetMaker;
    };

    $result = $assetMaker->getAssetPath('http://example.com/file.js');
    expect($result)->toBe('http://example.com/file.js');
});

it('returns symbolized path if file name is symbolized', function() {
    File::shouldReceive('symbolizePath')->with('~/file.js', null)->andReturn('/symbolized/path/file.js');
    $assetMaker = new class
    {
        use AssetMaker;
    };

    $result = $assetMaker->getAssetPath('~/file.js');
    expect($result)->toBe('/symbolized/path/file.js');
});

it('returns file path from asset path array', function() {
    File::shouldReceive('symbolizePath')->with('file.js', null)->andReturnNull();
    File::shouldReceive('symbolizePath')->with('/assets/file.js')->andReturn('/assets/file.js');
    File::shouldReceive('isFile')->with('/assets/file.js')->andReturn(true);
    $assetMaker = new class
    {
        use AssetMaker;
    };
    $assetMaker->assetPath = ['/assets'];

    $result = $assetMaker->getAssetPath('file.js');
    expect($result)->toBe('/assets/file.js');
});

it('returns original file name if file not found in asset path', function() {
    File::shouldReceive('symbolizePath')->andReturnNull();
    File::shouldReceive('isFile')->andReturn(false);
    $assetMaker = new class
    {
        use AssetMaker;
    };
    $assetMaker->assetPath = ['/assets'];

    $result = $assetMaker->getAssetPath('file.js');
    expect($result)->toBe('file.js');
});

it('adds JavaScript asset', function() {
    Assets::shouldReceive('addJs')->with('/assets/file.js', null)->once();
    File::shouldReceive('symbolizePath')->with('file.js', null)->andReturnNull();
    File::shouldReceive('symbolizePath')->with('/assets/file.js')->andReturn('/assets/file.js');
    File::shouldReceive('isFile')->with('/assets/file.js')->andReturn(true);
    $assetMaker = new class
    {
        use AssetMaker;
    };
    $assetMaker->assetPath = ['/assets'];

    $assetMaker->addJs('file.js');
});

it('adds CSS asset', function() {
    Assets::shouldReceive('addCss')->with('/assets/file.css', null)->once();
    File::shouldReceive('symbolizePath')->with('file.css', null)->andReturnNull();
    File::shouldReceive('symbolizePath')->with('/assets/file.css')->andReturn('/assets/file.css');
    File::shouldReceive('isFile')->with('/assets/file.css')->andReturn(true);
    $assetMaker = new class
    {
        use AssetMaker;
    };
    $assetMaker->assetPath = ['/assets'];

    $assetMaker->addCss('file.css');
});

it('adds RSS asset', function() {
    Assets::shouldReceive('addRss')->with('/assets/feed.rss', [])->once();
    File::shouldReceive('symbolizePath')->with('feed.rss', null)->andReturnNull();
    File::shouldReceive('symbolizePath')->with('/assets/feed.rss')->andReturn('/assets/feed.rss');
    File::shouldReceive('isFile')->with('/assets/feed.rss')->andReturn(true);
    $assetMaker = new class
    {
        use AssetMaker;
    };
    $assetMaker->assetPath = ['/assets'];

    $assetMaker->addRss('feed.rss');
});

it('adds meta asset', function() {
    Assets::shouldReceive('addMeta')->with(['name' => 'value'])->once();
    $assetMaker = new class
    {
        use AssetMaker;
    };

    $assetMaker->addMeta(['name' => 'value']);
});
