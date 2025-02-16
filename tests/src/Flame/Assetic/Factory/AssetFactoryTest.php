<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Factory;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Factory\AssetFactory;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use Igniter\Flame\Assetic\Filter\CssRewriteFilter;
use Igniter\Flame\Assetic\Filter\ScssphpFilter;
use Igniter\Flame\Support\Facades\File;

it('creates asset with default options', function() {
    $factory = new AssetFactory('/root');
    $factory->setDebug(false);
    $factory->setDefaultOutput('assetic/*');
    $asset = $factory->createAsset('input.css');

    expect($factory->isDebug())->toBeFalse()
        ->and($asset)->toBeInstanceOf(AssetCollection::class)
        ->and($asset->getTargetPath())->toBe('assetic/'
            .substr(
                sha1(serialize(['input.css']).serialize([]).serialize([
                    'debug' => false,
                    'output' => 'assetic/*',
                    'root' => ['/root'],
                ]),
                ), 0, 7,
            )
            .'.css',
        );
});

it('creates asset with nested input', function() {
    $factory = new AssetFactory('/root');
    $asset = $factory->createAsset([
        ['//example.com/input.css'],
        ['/path/input.css'],
    ]);

    expect($asset)->toBeInstanceOf(AssetCollection::class);
});

it('creates asset with custom options', function() {
    $factory = new AssetFactory('/root');
    $options = ['output' => 'custom/output', 'debug' => true, 'root' => '/custom/root'];
    $asset = $factory->createAsset('/root/input.css', [], $options);

    expect($asset)->toBeInstanceOf(AssetCollection::class)
        ->and($asset->getTargetPath())->toBe('custom/output.css');
});

it('creates asset with filters', function() {
    $filter = new CssImportFilter;
    $factory = new AssetFactory('/root');
    $asset = $factory->createAsset('input.css', [$filter], [
        'output' => 'assetic/{foo}/*',
        'vars' => ['foo', 'bar'],
    ]);

    expect($asset->getFilters())->toContain($filter);
});

it('generates asset name based on inputs, filters, and options', function() {
    $factory = new AssetFactory('/root');
    $name = $factory->generateAssetName(['input.css'], ['filter'], ['output' => 'output']);

    expect($name)->toBe(substr(sha1(serialize(['input.css']).serialize(['filter']).serialize(['output' => 'output'])), 0, 7));
});

it('returns last modified timestamp of asset', function() {
    $filter = new CssImportFilter;
    $filter2 = new CssRewriteFilter;
    $filter3 = mock(ScssphpFilter::class);
    $asset = new FileAsset('path/to/asset.css', [$filter, $filter2, $filter3]);
    $asset2 = new FileAsset('path/to/asset2.css');
    File::shouldReceive('isFile')->andReturn(true);
    File::shouldReceive('lastModified')->andReturn(1234567890);
    File::shouldReceive('get')->andReturn('asset content');
    $factory = new AssetFactory('/root');
    $filter3->shouldReceive('getChildren')->andReturn([$asset2]);

    expect($factory->getLastModified($asset))->toBe(1234567890);
});

it('returns last modified timestamp of asset collection', function() {
    $asset1 = new FileAsset('path/to/asset1.css');
    $asset2 = new FileAsset('path/to/asset2.css');
    File::shouldReceive('isFile')->andReturn(true);
    File::shouldReceive('lastModified')->with('path/to/asset1.css')->andReturn(0);
    File::shouldReceive('lastModified')->with('path/to/asset2.css')->andReturn(9876543210);
    $collection = new AssetCollection([$asset1, $asset2]);
    $factory = new AssetFactory('/root');

    expect($factory->getLastModified($collection))->toBe(9876543210);
});
