<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Attach\Observers;

use Igniter\Flame\Database\Attach\Media;
use Igniter\Flame\Database\Attach\Observers\MediaObserver;
use Symfony\Component\HttpFoundation\File\UploadedFile;

it('handles saved event with UploadedFile', function() {
    $media = mock(Media::class)->makePartial();
    $file = mock(UploadedFile::class);
    $media->fileToAdd = $file;

    $media->shouldReceive('addFromRequest')->once()->with($file);
    $observer = new MediaObserver;
    $observer->saved($media);

    expect($media->fileToAdd)->toBeNull();
});

it('handles saved event with file path', function() {
    $media = mock(Media::class)->makePartial();
    $filePath = '/path/to/file';
    $media->fileToAdd = $filePath;

    $media->shouldReceive('addFromFile')->once()->with($filePath);
    $observer = new MediaObserver;
    $observer->saved($media);

    expect($media->fileToAdd)->toBeNull();
});

it('handles saved event with null fileToAdd', function() {
    $media = mock(Media::class)->makePartial();
    $media->fileToAdd = null;

    $media->shouldNotReceive('addFromRequest');
    $media->shouldNotReceive('addFromFile');

    $observer = new MediaObserver;
    $observer->saved($media);

    expect($media->fileToAdd)->toBeNull();
});

it('handles deleted event', function() {
    $media = mock(Media::class)->makePartial();

    $media->shouldReceive('deleteThumbs')->once();
    $media->shouldReceive('deleteFile')->once();
    $observer = new MediaObserver;
    $observer->deleted($media);
});
