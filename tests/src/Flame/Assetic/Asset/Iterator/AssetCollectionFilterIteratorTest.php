<?php

namespace Igniter\Tests\Flame\Assetic\Asset\Iterator;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Asset\Iterator\AssetCollectionFilterIterator;
use Igniter\Flame\Assetic\Asset\Iterator\AssetCollectionIterator;

it('accepts unique asset based on strict equality', function() {
    $asset1 = new FileAsset('path1');
    $asset2 = new FileAsset('path2');
    $iterator = new AssetCollectionIterator(new AssetCollection([$asset1, $asset2]), new \SplObjectStorage);
    $filterIterator = new AssetCollectionFilterIterator($iterator);

    expect($filterIterator->accept())->toBeTrue();
    $iterator->next();
    expect($filterIterator->accept())->toBeTrue();
});

it('rejects duplicate asset based on strict equality', function() {
    $asset = new FileAsset('path1');
    $iterator = new AssetCollectionIterator(new AssetCollection([$asset, $asset]), new \SplObjectStorage);
    $filterIterator = new AssetCollectionFilterIterator($iterator);

    expect($filterIterator->accept())->toBeTrue();
    $iterator->next();
    expect($filterIterator->accept())->toBeFalse();
});

it('accepts unique asset based on source URL', function() {
    $asset1 = new FileAsset('root1/path1', [], 'root1');
    $asset2 = new FileAsset('root2/path2', [], 'root2');
    $iterator = new AssetCollectionIterator(new AssetCollection([$asset1, $asset2]), new \SplObjectStorage);
    $filterIterator = new AssetCollectionFilterIterator($iterator);

    expect($filterIterator->accept())->toBeTrue();
    $iterator->next();
    expect($filterIterator->accept())->toBeTrue();
});

it('rejects duplicate asset based on source URL', function() {
    $asset1 = new FileAsset('root/path', [], 'root');
    $asset2 = new FileAsset('root/path', [], 'root');
    $iterator = new AssetCollectionIterator(new AssetCollection([$asset1, $asset2]), new \SplObjectStorage);
    $filterIterator = new AssetCollectionFilterIterator($iterator);

    expect($filterIterator->accept())->toBeTrue();
    $iterator->next();
    expect($filterIterator->accept())->toBeFalse();
});

it('passes visited objects and source URLs to child iterator', function() {
    $childAsset = new FileAsset('path/to/child');
    $parentAsset = new AssetCollection([$childAsset]);
    $collection = new AssetCollection([$parentAsset]);
    $iterator = new AssetCollectionIterator($collection, new \SplObjectStorage);
    $filterIterator = new AssetCollectionFilterIterator($iterator);

    $children = $filterIterator->getChildren();
    expect($children)->toBeInstanceOf(AssetCollectionFilterIterator::class)
        ->and($children->accept())->toBeTrue();
});
