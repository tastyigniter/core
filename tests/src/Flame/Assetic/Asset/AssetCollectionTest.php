<?php

namespace Igniter\Tests\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use Igniter\Flame\Support\Facades\File;
use InvalidArgumentException;

it('adds asset to collection', function() {
    $asset = new FileAsset('path/to/asset');
    $collection = new AssetCollection([], [], 'path/to/source');

    $collection->add($asset);
    $collection->setContent('content');
    $collection->setTargetPath('path/to/target');
    $collection->setValues(['foo' => 'bar']);

    expect($collection->all())->toContain($asset)
        ->and($collection->getContent())->toBe('content')
        ->and($collection->getSourceRoot())->toBe('path/to/source')
        ->and($collection->getSourcePath())->toBeNull()
        ->and($collection->getSourceDirectory())->toBeNull()
        ->and($collection->getTargetPath())->toBe('path/to/target')
        ->and($collection->getValues())->toBe(['foo' => 'bar']);
});

it('removes leaf asset from collection', function() {
    $asset = new FileAsset('path/to/asset');
    $collection = new AssetCollection([$asset]);

    expect($collection->removeLeaf($asset))->toBeTrue()
        ->and($collection->all())->not->toContain($asset);
});

it('removes leaf asset from collection with child', function() {
    $childAsset = new FileAsset('path/to/child');
    $parentAsset = new AssetCollection([$childAsset]);
    $collection = new AssetCollection([$parentAsset]);

    expect($collection->removeLeaf($childAsset))->toBeTrue()
        ->and($collection->all())->not->toContain($childAsset);
});

it('throws exception when removing non-existent leaf asset', function() {
    $asset = new FileAsset('path/to/asset');
    $collection = new AssetCollection([]);

    expect(fn() => $collection->removeLeaf($asset))->toThrow(InvalidArgumentException::class)
        ->and($collection->removeLeaf($asset, true))->toBeFalse();
});

it('replaces leaf asset in collection', function() {
    $asset1 = new FileAsset('path/to/asset1');
    $asset2 = new FileAsset('path/to/asset2');
    $collection = new AssetCollection([$asset1]);

    expect($collection->replaceLeaf($asset1, $asset2))->toBeTrue()
        ->and($collection->all())->toContain($asset2)
        ->and($collection->all())->not->toContain($asset1);
});

it('replaces leaf asset from collection with child', function() {
    $childAsset1 = new FileAsset('path/to/child');
    $childAsset2 = new FileAsset('path/to/child');
    $parentAsset = new AssetCollection([$childAsset1]);
    $collection = new AssetCollection([$parentAsset]);

    expect($collection->replaceLeaf($childAsset1, $childAsset2))->toBeTrue()
        ->and($collection->all())->not->toContain($childAsset1);
});

it('throws exception when replacing non-existent leaf asset', function() {
    $asset1 = new FileAsset('path/to/asset1');
    $asset2 = new FileAsset('path/to/asset2');
    $collection = new AssetCollection([]);

    expect(fn() => $collection->replaceLeaf($asset1, $asset2))->toThrow(InvalidArgumentException::class)
        ->and($collection->replaceLeaf($asset1, $asset2, true))->toBeFalse();
});

it('loads assets and concatenates content', function() {
    $asset1 = new FileAsset('path/to/asset1');
    $asset1->setContent('content1');
    $asset2 = new FileAsset('path/to/asset2');
    $asset2->setContent('content2');
    $collection = new AssetCollection([$asset1, $asset2]);
    File::shouldReceive('isFile')->andReturnTrue();
    File::shouldReceive('get')->andReturn('content1', 'content2');

    $collection->load();
    expect($collection->getContent())->toBe("content1\ncontent2");
});

it('dumps assets and concatenates content', function() {
    $asset1 = new FileAsset('path/to/asset1');
    $asset1->setContent('content1');
    $asset2 = new FileAsset('path/to/asset2');
    $asset2->setContent('content2');
    $collection = new AssetCollection([$asset1, $asset2]);
    $collection->ensureFilter(new CssImportFilter);
    File::shouldReceive('isFile')->andReturnTrue();
    File::shouldReceive('get')->andReturn('content1', 'content2');

    expect($collection->dump())->toBe("content1\ncontent2")
        ->and($collection->getFilters())->not()->toBeEmpty()
        ->and($collection->clearFilters())->toBeNull()
        ->and($collection->getFilters())->toBeEmpty();
});

it('returns highest last modified timestamp of assets', function() {
    $asset1 = new FileAsset('path/to/asset1');
    $asset2 = new FileAsset('path/to/asset2');
    $collection = new AssetCollection([$asset1, $asset2]);
    File::shouldReceive('isFile')->andReturnTrue();
    File::shouldReceive('lastModified')->andReturn(1000, 2000);

    expect($collection->getLastModified())->toBe(2000);
});

it('returns zero when there are no assets for last modified timestamp', function() {
    $collection = new AssetCollection([]);
    $clonedCollection = clone $collection;

    expect($collection->getLastModified())->toBe(0)
        ->and($collection)->toEqual($clonedCollection);
});
