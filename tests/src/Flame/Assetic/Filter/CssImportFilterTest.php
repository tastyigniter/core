<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Factory\AssetFactory;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use Igniter\Flame\Support\Facades\File;

it('inlines imported stylesheets from absolute URL', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "http://example.com/style.css";');
    $asset->shouldReceive('setContent')->once();
    File::shouldReceive('get')->with('http://example.com/style.css')->andReturn('content');

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);

    expect($filter->getChildren(new AssetFactory('root'), 'content'))->toBe([]);
});

it('inlines imported stylesheets from relative URL', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('/path/style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "subdir/style.css";');
    $asset->shouldReceive('setContent')->once();
    File::shouldReceive('dirname')->with('/path/subdir/style.css')->andReturn('/path/subdir');
    File::shouldReceive('dirname')->with('/path/style.css')->andReturn('/path');
    File::shouldReceive('exists')->with('/path/subdir/style.css')->andReturnTrue();
    File::shouldReceive('exists')->with('/root//path/subdir/style.css')->andReturnTrue();
    File::shouldReceive('isFile')->with('/root//path/subdir/style.css')->andReturnTrue();
    File::shouldReceive('get')->with('/root//path/subdir/style.css')->andReturn('content');

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});

it('ignores non-existent imported stylesheets', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "nonexistent.css";');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});

it('inlines imported stylesheets from protocol-relative URL', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "//example.com/style.css";');
    $asset->shouldReceive('setContent')->once();
    File::shouldReceive('get')->with('http://example.com/style.css')->andReturn('content');

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});

it('inlines imported stylesheets from root-relative URL', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "/style.css";');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});

it('does not import stylesheet from empty import', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('@import "";');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});

it('does not import stylesheet from empty import if source path is null', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturnNull();
    $asset->shouldReceive('getContent')->andReturn('@import "subdir/style.css";');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssImportFilter;
    $filter->filterLoad($asset);
});
