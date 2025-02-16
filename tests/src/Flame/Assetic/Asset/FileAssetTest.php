<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use Igniter\Flame\Support\Facades\File;

it('constructs file asset with valid source and root', function() {
    $source = '/path/to/source/file.txt';

    $asset = new FileAsset($source);

    expect($asset->getSourceRoot())->toBe('/path/to/source')
        ->and($asset->getSourcePath())->toBe('file');
});

it('throws exception when source is not in root directory', function() {
    $source = '/path/to/source/file.txt';
    $filters = [];
    $sourceRoot = '/different/path';

    expect(fn() => new FileAsset($source, $filters, $sourceRoot))->toThrow(\InvalidArgumentException::class);
});

it('loads content from existing file', function() {
    $source = '/path/to/source/file.txt';
    $filters = [];
    $sourceRoot = '/path/to/source';

    File::shouldReceive('isFile')->with($source)->andReturn(true);
    File::shouldReceive('get')->with($source)->andReturn('file content');

    $asset = new FileAsset($source, $filters, $sourceRoot);
    $asset->load(new CssImportFilter);

    expect($asset->getContent())->toBe('file content');
});

it('throws exception when loading non-existent file', function() {
    $source = '/path/to/source/file.txt';
    $filters = [];
    $sourceRoot = '/path/to/source';
    $sourcePath = 'file.txt';

    File::shouldReceive('isFile')->with($source)->andReturn(false);

    $asset = new FileAsset($source, $filters, $sourceRoot, $sourcePath);

    expect(fn() => $asset->load())->toThrow(\RuntimeException::class);
});

it('returns last modified timestamp of existing file', function() {
    $source = '/path/to/source/file.txt';
    $filters = [];
    $sourceRoot = '/path/to/source';
    $sourcePath = 'file.txt';

    File::shouldReceive('isFile')->with($source)->andReturn(true);
    File::shouldReceive('lastModified')->with($source)->andReturn(1234567890);

    $asset = new FileAsset($source, $filters, $sourceRoot, $sourcePath);

    expect($asset->getLastModified())->toBe(1234567890);
});

it('throws exception when getting last modified timestamp of non-existent file', function() {
    $source = '/path/to/source/file.txt';
    $filters = [];
    $sourceRoot = '/path/to/source';
    $sourcePath = 'file.txt';

    File::shouldReceive('isFile')->with($source)->andReturn(false);

    $asset = new FileAsset($source, $filters, $sourceRoot, $sourcePath);

    expect(fn() => $asset->getLastModified())->toThrow(\RuntimeException::class);
});
