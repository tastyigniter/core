<?php

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Database\Attach\Manipulator;
use Igniter\Main\Classes\MediaLibrary;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function() {
    config([
        'igniter-system.assets.media.ignore' => ['file-ignore.png'],
        'igniter-system.assets.media.ignorePatterns' => ['file-pattern.*'],
    ]);

    $this->mediaLibrary = resolve(MediaLibrary::class);
});

it('lists all folders recursively', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('directories')->andReturn(['', 'folder1', 'folder2/subfolder', 'exclude/folder']);

    expect($this->mediaLibrary->listAllFolders(null, ['exclude']))->toBe(['/', 'folder1', 'folder2/subfolder']);
});

it('ensures root path exists in listed folders', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('directories')->andReturn(['folder1', 'folder2/subfolder', 'exclude/folder']);

    expect($this->mediaLibrary->listAllFolders(null, ['exclude']))->toBe(['/', 'folder1', 'folder2/subfolder']);
});

it('lists files from cached', function() {
    Cache::put('main.media.contents', base64_encode(serialize([
        'single.directories./' => ['file1.jpg', 'file2.jpg'],
    ])));

    expect($this->mediaLibrary->listFolderContents('/', 'directories'))->toBe(['file1.jpg', 'file2.jpg']);
});

it('fetches files with search and filter options', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('files')->andReturn(['file1.jpg', 'file2.mp3']);
    $filesystem->shouldReceive('lastModified')->andReturn(time());
    $filesystem->shouldReceive('size')->andReturn(1024, 2048);
    $filesystem->shouldReceive('url')->andReturn('http://example.com/file1.jpg', 'http://example.com/file2.mp3');

    $result = $this->mediaLibrary->fetchFiles('/', ['name', 'ascending'], 'file1');

    expect($result)->toHaveCount(1)->and($result[0]->name)->toBe('file1.jpg');
});

it('skips ignored files and sorts by date when fetching files', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('files')->andReturn(['file2.jpg', 'file1.jpg', 'file3.jpg', 'file-ignore.png', 'file-pattern.jpg']);
    $filesystem->shouldReceive('lastModified')->andReturn(
        now()->subMinutes(5)->timestamp,
        now()->subMinutes(3)->timestamp,
        now()->subMinutes(2)->timestamp,
    );
    $filesystem->shouldReceive('size')->andReturn(1024, 2048, 208);
    $filesystem->shouldReceive('url')->andReturn('http://example.com/file1.jpg');

    $result = $this->mediaLibrary->fetchFiles('/', ['date', 'ascending'], ['filter' => 'image']);

    expect($result[0])->name->toBe('file3.jpg');
});

it('sorts by date when fetching files', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('files')->andReturn(['file2.jpg', 'file1.jpg', 'file3.jpg', 'file-ignore.png', 'file-pattern.jpg']);
    $filesystem->shouldReceive('lastModified')->andReturn(now()->timestamp);
    $filesystem->shouldReceive('size')->andReturn(1024, 2048, 208);
    $filesystem->shouldReceive('url')->andReturn('http://example.com/file1.jpg');

    $result = $this->mediaLibrary->fetchFiles('/', ['size', 'descending'], ['filter' => 'image']);

    expect($result[0])->name->toBe('file3.jpg');
});

it('returns file contents when get is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('get')->andReturn('file-contents');

    expect($this->mediaLibrary->get('file.jpg'))->toBe('file-contents');
});

it('returns file stream when get is called with stream true', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('readStream')->andReturn('file-stream');

    expect($this->mediaLibrary->get('file.jpg', true))->toBe('file-stream');
});

it('saves file contents when put is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('put')->andReturnTrue();

    expect($this->mediaLibrary->put('file.jpg', 'file-contents'))->toBeTrue();
});

it('creates a folder when makeFolder is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('makeDirectory')->andReturnTrue();

    expect($this->mediaLibrary->makeFolder('new-folder'))->toBeTrue();
});

it('copies a file when copyFile is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('copy')->andReturnTrue();

    expect($this->mediaLibrary->copyFile('src-file.jpg', 'dest-file.jpg'))->toBeTrue();
});

it('moves a file when moveFile is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('move')->andReturnTrue();

    expect($this->mediaLibrary->moveFile('src-file.jpg', 'new-file.jpg'))->toBeTrue();
});

it('renames a file when rename is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('move')->andReturnTrue();

    expect($this->mediaLibrary->rename('old-file.jpg', 'new-file.jpg'))->toBeTrue();
});

it('deletes multiple files when deleteFiles is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('delete')->andReturnTrue();

    expect($this->mediaLibrary->deleteFiles(['file1.jpg', 'file2.jpg']))->toBeTrue();
});

it('deletes a folder when deleteFolder is called', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('deleteDirectory')->andReturnTrue();

    expect($this->mediaLibrary->deleteFolder('folder'))->toBeTrue();
});

it('returns media URL for a given path', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('url')->andReturn('http://example.com/media/uploads/file.jpg');

    $mediaLibrary = $this->mediaLibrary;

    expect($mediaLibrary->getMediaUrl('file.jpg'))->toBe('http://example.com/media/uploads/file.jpg')
        ->and($mediaLibrary->getMediaUrl('http://example.com/file.jpg'))->toBe('http://example.com/file.jpg');
});

it('returns true if file exists', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('exists')->andReturnTrue();

    expect($this->mediaLibrary->exists('file.jpg'))->toBeTrue();
});

it('returns uploads path when path starts with storage folder', function() {
    expect($this->mediaLibrary->getUploadsPath('media/uploads/file.jpg'))->toBe('media/uploads/file.jpg');
});

it('returns validated uploads path when path does not start with storage folder', function() {
    expect($this->mediaLibrary->getUploadsPath('file.jpg'))->toBe('media/uploads/file.jpg');
});

it('returns media thumb URL if thumb file exists', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('exists')->andReturnTrue();
    $filesystem->shouldReceive('url')->andReturn('http://example.com/thumb.jpg');

    expect($this->mediaLibrary->getMediaThumb('file.jpg'))->toBe('http://example.com/thumb.jpg');
});

it('returns default thumb path if original file does not exist', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('exists')->andReturn(false, false, false);
    $filesystem->shouldReceive('makeDirectory')->andReturnTrue();
    $filesystem->shouldReceive('getDefaultThumbPath')->andReturn('default-thumb.jpg');
    $filesystem->shouldReceive('put')->andReturnSelf();
    app()->instance(Manipulator::class, $manipulator = mock(Manipulator::class)->makePartial());
    $manipulator->shouldReceive('manipulate')->andReturnSelf();
    $manipulator->shouldReceive('save')->andReturnSelf();
    $filesystem->shouldReceive('url')->andReturn('http://example.com/default-thumb.jpg');
    $filesystem->shouldReceive('path')->andReturn('/path/to/default-thumb.jpg');

    expect($this->mediaLibrary->getMediaThumb('file.jpg', ['default' => 'default-thumb.jpg']))
        ->toBe('http://example.com/default-thumb.jpg');
});

it('creates and returns default thumb path if original file does not exist', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('exists')->andReturn(false, true, false);
    $filesystem->shouldReceive('getDefaultThumbPath')->andReturn('default-thumb.jpg');
    $filesystem->shouldReceive('put')->andReturnSelf();
    app()->instance(Manipulator::class, $manipulator = mock(Manipulator::class)->makePartial());
    $manipulator->shouldReceive('manipulate')->andReturnSelf();
    $manipulator->shouldReceive('save')->andReturnSelf();
    $filesystem->shouldReceive('url')->andReturn('http://example.com/default-thumb.jpg');

    expect($this->mediaLibrary->getMediaThumb('file.jpg'))->toBe('http://example.com/default-thumb.jpg');
});

it('returns media relative path when path starts with storage folder', function() {
    expect($this->mediaLibrary->getMediaRelativePath('media/uploads/file.jpg'))->toBe('file.jpg');
});

it('returns original path when path does not start with storage folder', function() {
    expect($this->mediaLibrary->getMediaRelativePath('file.jpg'))->toBe('file.jpg');
});

it('returns config value for given key', function() {
    expect($this->mediaLibrary->getConfig())->toBeArray()
        ->and($this->mediaLibrary->getConfig('path'))->toBe('media/uploads/');
});

it('returns default value when config key is not found', function() {
    expect($this->mediaLibrary->getConfig('nonexistent', 'default'))->toBe('default');
});

it('returns allowed extensions from settings', function() {
    expect($this->mediaLibrary->getAllowedExtensions())->toContain('jpg', 'png', 'gif');
});

it('returns true if extension is allowed', function() {
    expect($this->mediaLibrary->isAllowedExtension('jpg'))->toBeTrue();
});

it('returns false if extension is not allowed', function() {
    expect($this->mediaLibrary->isAllowedExtension('exe'))->toBeFalse();
});

it('resets cache by forgetting cache key', function() {
    expect($this->mediaLibrary->resetCache())->toBeNull();
});

it('returns folder size', function() {
    $filesystem = mock(Filesystem::class);
    Storage::shouldReceive('disk')->andReturn($filesystem);
    $filesystem->shouldReceive('files')->andReturn(['file1.jpg', 'file2.jpg']);
    $filesystem->shouldReceive('lastModified')->andReturn(time());
    $filesystem->shouldReceive('size')->andReturn(1024, 2048);
    $filesystem->shouldReceive('url')->andReturn('http://example.com/file1.jpg', 'http://example.com/file2.jpg');

    expect($this->mediaLibrary->folderSize('/'))->toBe(3072);
});
