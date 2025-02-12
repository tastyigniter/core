<?php

namespace Igniter\Tests\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Filter\FilterCollection;
use Igniter\Flame\Assetic\Filter\FilterInterface;

it('adds a single filter to the collection', function() {
    $filter = mock(FilterInterface::class);
    $collection = new FilterCollection;
    $collection->ensure($filter);

    expect($collection->count())->toBe(1)
        ->and($collection->all())->toContain($filter);
});

it('adds a nested filters to the collection', function() {
    $filter = mock(FilterInterface::class);
    $mockCollection = new FilterCollection([$filter]);
    $collection = new FilterCollection;
    $collection->ensure($mockCollection);

    expect($collection->count())->toBe(1)
        ->and($collection->all())->toContain($filter);
});

it('adds multiple filters to the collection', function() {
    $filter1 = mock(FilterInterface::class);
    $filter2 = mock(FilterInterface::class);
    $collection = new FilterCollection([$filter1, $filter2]);

    expect($collection->count())->toBe(2)
        ->and($collection->all())->toContain($filter1, $filter2);
});

it('does not add duplicate filters to the collection', function() {
    $filter = mock(FilterInterface::class);
    $collection = new FilterCollection([$filter]);
    $collection->ensure($filter);

    expect($collection->count())->toBe(1);
});

it('clears all filters from the collection', function() {
    $filter = mock(FilterInterface::class);
    $collection = new FilterCollection([$filter]);
    $collection->clear();

    expect($collection->count())->toBe(0)
        ->and($collection->all())->toBeEmpty();
});

it('applies filterLoad to all filters in the collection', function() {
    $asset = mock(AssetInterface::class);
    $filter = mock(FilterInterface::class);
    $filter->shouldReceive('filterLoad')->once()->with($asset);
    $collection = new FilterCollection([$filter]);

    $collection->filterLoad($asset);
});

it('applies filterDump to all filters in the collection', function() {
    $asset = mock(AssetInterface::class);
    $filter = mock(FilterInterface::class);
    $filter->shouldReceive('filterDump')->once()->with($asset);
    $collection = new FilterCollection([$filter]);

    $collection->filterDump($asset);
});
