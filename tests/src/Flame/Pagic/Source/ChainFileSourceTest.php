<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic\Source;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Pagic\Source\ChainFileSource;
use Igniter\Flame\Pagic\Source\SourceInterface;

it('selects a file from the first source that has it', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('select')->andReturn(null);
    $source2->shouldReceive('select')->andReturn(['filePath' => 'path/to/file']);
    $chainSource = new ChainFileSource([$source1, $source2]);

    expect($chainSource->select('dir', 'file', 'ext'))->toBe(['filePath' => 'path/to/file']);
});

it('returns null if no source has the file', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('select')->andReturn(null);
    $source2->shouldReceive('select')->andReturn(null);
    $chainSource = new ChainFileSource([$source1, $source2]);
    $result = $chainSource->select('dir', 'file', 'ext');
    expect($result)->toBeNull();
});

it('selects all files from all sources and removes duplicates', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('selectAll')->andReturn([['fileName' => 'file1'], ['fileName' => 'file2']]);
    $source2->shouldReceive('selectAll')->andReturn([['fileName' => 'file2'], ['fileName' => 'file3']]);
    $chainSource = new ChainFileSource([$source1, $source2]);

    expect($chainSource->selectAll('dir'))->toBe([['fileName' => 'file2'], ['fileName' => 'file3'], ['fileName' => 'file1']]);
});

it('inserts a file into the active source', function() {
    $source = mock(SourceInterface::class);
    $source->shouldReceive('insert')->andReturn(true);
    $chainSource = new ChainFileSource([$source]);

    expect($chainSource->insert('dir', 'file', 'ext', 'content'))->toBeTrue();
});

it('updates a file in the active source', function() {
    $source = mock(SourceInterface::class);
    $source->shouldReceive('update')->andReturnTrue();
    $chainSource = new ChainFileSource([$source]);

    expect($chainSource->update('dir', 'file', 'ext', 'content'))->toBeTrue();
});

it('deletes a file from the active source', function() {
    $source = mock(SourceInterface::class);
    $source->shouldReceive('delete')->andReturnTrue();
    $chainSource = new ChainFileSource([$source]);

    expect($chainSource->delete('dir', 'file', 'ext'))->toBeTrue();
});

it('returns the last modified date from the first source that has it', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('lastModified')->andReturn(null, null);
    $source2->shouldReceive('lastModified')->andReturn(1234567890, null);
    $chainSource = new ChainFileSource([$source1, $source2]);

    expect($chainSource->lastModified('dir', 'file', 'ext'))->toBe(1234567890)
        ->and($chainSource->lastModified('dir', 'file', 'ext'))->toBeNull();
});

it('returns the path from the first source that has it', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('path')->andReturn(null);
    $source2->shouldReceive('path')->andReturn('path/to/file');
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->with(null)->andReturn(false);
    $files->shouldReceive('exists')->with('path/to/file')->andReturn(true);
    app()->instance(Filesystem::class, $files);
    $chainSource = new ChainFileSource([$source1, $source2]);

    expect($chainSource->path('path/to/file'))->toBe('path/to/file');
});

it('returns the original path if no source has it', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('path')->andReturn(null);
    $source2->shouldReceive('path')->andReturn(null);
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->with(null)->andReturn(false);
    $files->shouldReceive('exists')->with('path/to/file')->andReturn(false);
    app()->instance(Filesystem::class, $files);
    $chainSource = new ChainFileSource([$source1, $source2]);
    $result = $chainSource->path('path/to/file');
    expect($result)->toBe('path/to/file');
});

it('generates a unique cache key for the source', function() {
    $source1 = mock(SourceInterface::class);
    $source2 = mock(SourceInterface::class);
    $source1->shouldReceive('makeCacheKey')->andReturn(1234);
    $source2->shouldReceive('makeCacheKey')->andReturn(12345);
    $chainSource = new ChainFileSource([$source1, $source2]);

    expect($chainSource->makeCacheKey('name'))->toBe(crc32('1234-12345-'));
});
