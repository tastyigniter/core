<?php

namespace Igniter\Tests\Main\Classes;

use Carbon\Carbon;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\MediaItem;

it('returns true when item is a file', function() {
    $mediaItem = new MediaItem('path/to/file.jpg', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.jpg');
    expect($mediaItem->isFile())->toBeTrue();
});

it('returns correct file type for image', function() {
    $mediaItem = new MediaItem('path/to/file.jpg', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.jpg');
    expect($mediaItem->getFileType())->toBe(MediaItem::FILE_TYPE_IMAGE);
});

it('returns correct file type for audio', function() {
    $mediaItem = new MediaItem('path/to/file.mp3', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.mp3');
    expect($mediaItem->getFileType())->toBe(MediaItem::FILE_TYPE_AUDIO);
});

it('returns correct file type for video', function() {
    $mediaItem = new MediaItem('path/to/file.mp4', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.mp4');
    expect($mediaItem->getFileType())->toBe(MediaItem::FILE_TYPE_VIDEO);
});

it('returns false when item is missing extension', function() {
    $mediaItem = new MediaItem('path/to/file-with-no-extension', null, time(), MediaItem::TYPE_FOLDER, 'http://example.com/file-with-no-extension');
    expect($mediaItem->isFile())->toBeFalse();
});

it('returns document file type for unknown extension', function() {
    $mediaItem = new MediaItem('path/to/file.unknown', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.unknown');
    expect($mediaItem->getFileType())->toBe(MediaItem::FILE_TYPE_DOCUMENT);
});

it('returns formatted size string for file', function() {
    File::shouldReceive('sizeToString')->with(1024)->andReturn('1 KB');
    $mediaItem = new MediaItem('path/to/file.jpg', 1024, time(), MediaItem::TYPE_FILE, 'http://example.com/file.jpg');
    expect($mediaItem->sizeToString())->toBe('1 KB');
});

it('returns formatted size string for folder', function() {
    $mediaItem = new MediaItem('path/to/folder', 10, time(), MediaItem::TYPE_FOLDER, 'folder');
    expect($mediaItem->sizeToString())->toBe('10 Items');
});

it('returns formatted last modified date string', function() {
    $timestamp = time();
    $mediaItem = new MediaItem('path/to/file.jpg', 1024, $timestamp, MediaItem::TYPE_FILE, 'http://example.com/file.jpg');
    expect($mediaItem->lastModifiedAsString())->toBe(Carbon::now()->toFormattedDateString());
});

it('returns null for last modified date when not set', function() {
    $mediaItem = new MediaItem('path/to/file.jpg', 1024, null, MediaItem::TYPE_FILE, 'http://example.com/file.jpg');
    expect($mediaItem->lastModifiedAsString())->toBeNull();
});
