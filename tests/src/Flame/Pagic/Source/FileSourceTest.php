<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic\Source;

use Exception;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Pagic\Source\FileSource;
use Igniter\Flame\Pagic\Source\SourceResolver;
use Igniter\Main\Classes\ThemeManager;

beforeEach(function() {
    $theme = resolve(ThemeManager::class)->findTheme('tests-theme');
    $this->source = $theme->makeFileSource();
});

it('returns a single template when file exists', function() {
    $result = $this->source->select('_pages', 'nested-page', 'blade.php');
    expect($result)->toHaveKeys(['fileName', 'mTime', 'content'])
        ->and($result)->toHaveKey('fileName', 'nested-page.blade.php');
});

it('returns null when file does not exist', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('lastModified')->andThrow(new Exception);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->select('dir', 'file', 'ext'))->toBeNull();
});

it('returns all templates in directory', function() {
    expect($this->source->selectAll('_pages'))->not()->toBeEmpty();

    $files = mock(Filesystem::class);
    $files->shouldReceive('isDirectory')->andReturn(false);
    expect((new FileSource('base/path', $files))->selectAll('_pages'))->toBe([]);

    $options = [
        'columns' => ['column'],
        'extensions' => ['txt'],
    ];
    expect($this->source->selectAll('_pages', $options))->toBeEmpty();

    $options = [
        'columns' => ['column'],
        'fileMatch' => 'file*',
    ];
    expect($this->source->selectAll('_pages', $options))->toBeEmpty();
});

it('creates a new template when file does not exist', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturn(true);
    $files->shouldReceive('isDirectory')->andReturn(true);
    $files->shouldReceive('makeDirectory')->andReturn(true);
    $files->shouldReceive('isFile')->andReturn(false);
    $files->shouldReceive('put')->andReturn(true);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->insert('dir', 'file', 'ext', 'content'))->toBeTrue();
});

it('throws exception when creating a template', function() {
    $files = mock(Filesystem::class);
    $fileSource = new FileSource('base/path', $files);
    $files->shouldReceive('exists')->with('base/path/dir')->andReturn(false, true);
    $files->shouldReceive('makeDirectory')->withSomeOfArgs('base/path/dir')->andReturn(false, true);

    expect(fn() => $fileSource->insert('dir', 'file', 'blade.php', 'content'))
        ->toThrow('Error creating directory [base/path/dir]. Please check write permissions.');

    $files->shouldReceive('isDirectory')->with('base/path/dir')->andReturn(false);
    $files->shouldReceive('isDirectory')->with('base/path/dir/subdir')->andReturn(false);
    $files->shouldReceive('dirname')->with('base/path/dir/subdir/file.blade.php')->andReturn('base/path/dir/subdir');
    $files->shouldReceive('makeDirectory')->withSomeOfArgs('base/path/dir/subdir')->andReturn(false);
    expect(fn() => $fileSource->insert('dir', 'subdir/file', 'blade.php', 'content'))
        ->toThrow('Error creating directory [base/path/dir/subdir]. Please check write permissions.');

    $files->shouldReceive('isFile')->with('base/path/dir/file.blade.php')->andReturn(false, true);
    expect(fn() => $fileSource->insert('dir', 'file', 'blade.php', 'content'))
        ->toThrow('Error creating file [base/path/dir/file.blade.php]. Please check write permissions.')
        ->and(fn() => $fileSource->insert('dir', 'file', 'blade.php', 'content'))
        ->toThrow('A file already exists at [base/path/dir/file.blade.php].');

});

it('updates an existing template', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturn(true);
    $files->shouldReceive('isDirectory')->andReturn(true);
    $files->shouldReceive('put')->andReturn(1);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->update('dir', 'file', 'blade.php', 'content'))->toBeTrue();
});

it('throws exception when updating a template', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturn(true);
    $files->shouldReceive('isDirectory')->andReturn(true);
    $files->shouldReceive('makeDirectory')->andReturn(true);
    $files->shouldReceive('isFile')->andReturn(true);
    $files->shouldReceive('delete')->andReturn(1);
    $fileSource = new FileSource('base/path', $files);

    expect(fn() => $fileSource->update('dir', 'file', 'blade.php', 'content', 'new-file'))
        ->toThrow('A file already exists at [base/path/dir/file.blade.php].');

    $files->shouldReceive('put')->withSomeOfArgs('bade/path/file.blade.php')->andThrow(new Exception);
    expect(fn() => $fileSource->update('dir', 'file', 'blade.php', 'content'))
        ->toThrow('Error creating file [base/path/dir/file.blade.php]. Please check write permissions.');
});

it('deletes an existing template', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('delete')->andReturn(1);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->delete('dir', 'file', 'blade.php'))->toBeTrue();
});

it('throws exception when deleting a template', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('delete')->andThrow(new Exception);
    $fileSource = new FileSource('base/path', $files);

    expect(fn() => $fileSource->delete('dir', 'file', 'blade.php'))
        ->toThrow('Error deleting file [base/path/dir/file.blade.php]. Please check write permissions.');
});

it('returns the last modified date of a file', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('lastModified')->andReturn(1234567890);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->lastModified('dir', 'file', 'blade.php'))->toBe(1234567890)
        ->and($fileSource->getPathsCacheKey())->toStartWith('pagic-source-file-');
});

it('returns null if last modified date cannot be retrieved', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('lastModified')->andThrow(new Exception);
    $fileSource = new FileSource('base/path', $files);

    expect($fileSource->lastModified('dir', 'file', 'blade.php'))->toBeNull();
});

it('initializes source resolver with sources', function() {
    $files = mock(Filesystem::class);
    $sourceResolver = new SourceResolver([
        'child' => new FileSource('base/path', $files),
        'parent' => new FileSource('base/path', $files),
    ]);

    expect($sourceResolver->hasSource('child'))->toBeTrue()
        ->and($sourceResolver->hasSource('parent'))->toBeTrue();
});
