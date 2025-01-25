<?php

namespace Igniter\Tests\Main\Helpers;

use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Helpers\ImageHelper;

it('returns resized image path with given width and height', function() {
    config()->set('igniter-system.assets.media.folder', 'data');
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class));
    $mediaLibrary->shouldReceive('getMediaThumb')->with('path/to/image.jpg', [
        'width' => 100,
        'height' => 200,
    ])->andReturn('resized/image.jpg');

    expect(ImageHelper::resize('path/to/image.jpg', 100, 200))->toBe('resized/image.jpg');
});

it('returns resized image path with given options array', function() {
    config()->set('igniter-system.assets.media.folder', 'data');
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class));
    $mediaLibrary->shouldReceive('getMediaThumb')->with('path/to/image.jpg', [
        'width' => 150,
        'height' => 150,
        'fit' => 'crop',
    ])->andReturn('resized/image.jpg');

    expect(ImageHelper::resize('path/to/image.jpg', [
        'width' => 150,
        'height' => 150,
        'fit' => 'crop',
    ]))->toBe('resized/image.jpg');
});

it('returns resized image path with root folder stripped from path', function() {
    config()->set('igniter-system.assets.media.folder', 'data');
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class));
    $mediaLibrary->shouldReceive('getMediaThumb')->with('image.jpg', [
        'width' => 100,
        'height' => 200,
    ])->andReturn('resized/image.jpg');

    expect(ImageHelper::resize('data/image.jpg', 100, 200))->toBe('resized/image.jpg');
});
