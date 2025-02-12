<?php

namespace Igniter\Tests\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Filter\CssRewriteFilter;

it('rewrites relative URLs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('http://example.com/path/to/root/');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('css/compiled/style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("../images/bg.png"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});

it('rewrites .. relative URLs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('http://example.com/path/../root/');
    $asset->shouldReceive('getSourcePath')->andReturn('style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('css/compiled/style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("../images/bg.png"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});

it('rewrites root-relative URLs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('css/style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('compiled/style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("/images/bg.png"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});

it('ignores absolute URLs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('css/style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('css/compiled/style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("http://example.com/images/bg.png"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});

it('ignores protocol-relative URLs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('css/style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('css/compiled/style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("//example.com/images/bg.png"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});

it('ignores data URIs in CSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('css/style.css');
    $asset->shouldReceive('getTargetPath')->andReturn('style.css');
    $asset->shouldReceive('getContent')->andReturn('body { background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA"); }');
    $asset->shouldReceive('setContent')->once();

    $filter = new CssRewriteFilter();
    $filter->filterDump($asset);
});
