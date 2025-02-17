<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Asset\Iterator;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Asset\Iterator\AssetCollectionIterator;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use SplObjectStorage;

it('returns current asset with filters and target URL applied', function() {
    $asset = new FileAsset('path/to/asset/{source}.scss');
    $collection = new AssetCollection([$asset], [new CssImportFilter], null, ['source', 'bar']);
    $collection->setTargetPath('path/to/target/{source}.css');

    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $result = $iterator->current();
    expect($result)->toBeInstanceOf(FileAsset::class)
        ->and($result->getTargetPath())->toContain('path/to/target');
});

it('returns raw current asset without modifications', function() {
    $asset = new FileAsset('path/to/asset/source.css');
    $collection = new AssetCollection([$asset]);
    $collection->setTargetPath('path/to/target.css');

    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $result = $iterator->current(true);
    expect($result)->toBe($asset);
});

it('returns null when there are no assets', function() {
    $collection = new AssetCollection([]);
    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    expect($iterator->current())->toBeFalse();
});

it('iterates over assets correctly', function() {
    $asset1 = new FileAsset('path/to/asset1.css');
    $asset2 = new FileAsset('path/to/asset2.css');
    $collection = new AssetCollection([$asset1, $asset2]);
    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $iterator->rewind();
    expect($iterator->current()->getSourcePath())->toBe($asset1->getSourcePath())
        ->and($iterator->current()->getSourcePath())->toBe($asset1->getSourcePath());

    $iterator->next();
    expect($iterator->current()->getSourcePath())->toBe($asset2->getSourcePath());

    $iterator->next();
    expect($iterator->valid())->toBeFalse();
});

it('returns true if current asset has children', function() {
    $childAsset = new FileAsset('path/to/child');
    $parentAsset = new AssetCollection([$childAsset]);
    $collection = new AssetCollection([$parentAsset]);
    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $iterator->rewind();

    expect($iterator->hasChildren())->toBeTrue();
});

it('returns false if current asset has no children', function() {
    $asset = new FileAsset('path/to/asset');
    $collection = new AssetCollection([$asset]);
    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $iterator->rewind();

    expect($iterator->hasChildren())->toBeFalse();
});

it('returns child iterator for current asset', function() {
    $childAsset = new FileAsset('path/to/child');
    $parentAsset = new AssetCollection([$childAsset]);
    $collection = new AssetCollection([$parentAsset]);
    $iterator = new AssetCollectionIterator($collection, new SplObjectStorage);

    $iterator->rewind();

    $children = $iterator->getChildren();
    expect($children)->toBeInstanceOf(AssetCollectionIterator::class)
        ->and($children->current()->getSourcePath())->toBe($childAsset->getSourcePath());
});
