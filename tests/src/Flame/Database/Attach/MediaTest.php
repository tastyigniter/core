<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Attach;

use Igniter\Flame\Database\Attach\Manipulator;
use Igniter\Flame\Database\Attach\Media;
use Igniter\Flame\Database\Attach\MediaAdder;
use Igniter\Tests\Flame\Database\Fixtures\TestModelForMedia;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

it('guesses file extension correctly', function() {
    $media1 = new Media;
    $media2 = new Media;
    $media1->file_name = 'file.jpg';
    $media1->setCustomProperty('extension', 'jpg');
    $media1->setCustomProperty('forget', 'jpg');
    $media1->forgetCustomProperty('forget');

    expect($media1->getMimeType())->toBe('image/jpeg')
        ->and($media2->getMimeType())->toBeNull()
        ->and($media1->getCustomProperties())->toHaveKey('extension')->not()->toHaveKey('forget')
        ->and($media1->attachment())->toBeInstanceOf(MorphTo::class);
});

it('adds file from request', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();
    $uploadedFile = UploadedFile::fake()->image('file.jpg');
    $media->addFromRequest($uploadedFile);

    $storageMock->assertExists($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
});

it('adds file from disk', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();
    $media->addFromFile(__DIR__.'/../Fixtures/test.png');

    $storageMock->assertExists($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
    expect($media->path)->toBe($media->getPublicPath().$media->getPartitionDirectory().$media->name)
        ->and($media->getFullDiskPath())->toBeString()
        ->and($media->sizeToString())->toBe($media->human_readable_size)
        ->and($media->getFilename())->toBe('test.png')
        ->and($media->type)->toBe('image/png')
        ->and($media->extension)->toBe('png')
        ->and($media->width)->toBe(400)
        ->and($media->height)->toBe(300)
        ->and($media->getThumb('contain'))->not()->toBeNull()
        ->and($media->getLastModified())->toBeGreaterThan(0)
        ->and($media->getUniqueName())->toBe($media->name);
});

it('uses the default blank thumb when no default is provided', function() {
    $storageMock = Storage::fake('public');
    $media = new Media;

    $result = $media->getDefaultThumbPath('/path/to/thumb.png');

    $storageMock->assertExists('/path/to/thumb.png');
    expect($result)->toEndWith('/path/to/thumb.png');
});

it('adds file from disk and creates related model', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $media = $model->newMediaInstance();

    app(MediaAdder::class)->useDisk('media');
    $media->addFromFile(__DIR__.'/../Fixtures/test.png');
    $model->save();

    $storageMock->assertExists($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
    expect($media->deleteFile())->toBeNull();

    File::deleteDirectory($media->getTempPath().'/path');
});

it('adds non image file from disk', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();
    $media->addFromFile(__DIR__.'/../Fixtures/test.pdf');

    $expectedFile = $media->getStorageDirectory().$media->getPartitionDirectory().$media->name;
    $expectedThumb = $media->getPublicPath().$media->getPartitionDirectory().$media->name;
    $storageMock->assertExists($expectedFile);
    expect($media->getThumb())->toBe($expectedThumb)
        ->and($media->deleteFile())->toBeNull();
});

it('adds file from raw data', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();

    $media->addFromRaw(Manipulator::decodedBlankImage(), 'path/file.png');

    $storageMock->assertExists($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
    expect($media->deleteFile())->toBeNull();

    File::deleteDirectory($media->getTempPath().'/path');
});

it('throws exception when adding file from unreachable url', function() {
    Http::fake([
        'http://example.com/file.jpg' => Http::response('', 404),
    ]);
    $media = new Media;
    $url = 'http://example.com/file.jpg';
    $media->addFromUrl($url, 'file.jpg');
})->throws(RuntimeException::class, sprintf('Error opening file "%s"', 'http://example.com/file.jpg'));

it('adds file from url', function() {
    Http::fake([
        'http://example.com/file.jpg' => Http::response(file_get_contents(__DIR__.'/../Fixtures/test.png')),
    ]);
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();
    $url = 'http://example.com/file.jpg';
    $filename = 'file.jpg';

    $media->addFromUrl($url, $filename);

    $storageMock->assertExists($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
    File::deleteDirectory($media->getTempPath());
    expect($media->deleteThumbs())->toBeNull();
});

it('it does not delete empty directory after deleting file', function() {
    $storageMock = Storage::fake('public');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $model = new TestModelForMedia;
    $model->save();

    $media = $model->newMediaInstance();

    $media->addFromFile(__DIR__.'/../Fixtures/test.png');

    $storageMock->assertExists($media->getStorageDirectory().$media->getPartitionDirectory().$media->name);

    $storageMock->put($media->getStorageDirectory().$media->getPartitionDirectory().'/test.txt', 'test');

    expect($media->addFromFile(__DIR__.'/../Fixtures/test.png')->deleteFile())->toBeNull();

    $storageMock->delete($media->getStorageDirectory().$media->getPartitionDirectory().'/test.txt');
    $storageMock->put(dirname($media->getStorageDirectory().$media->getPartitionDirectory()).'/test.txt', 'test');

    expect($media->addFromFile(__DIR__.'/../Fixtures/test.png')->deleteFile())->toBeNull();

    $storageMock->delete(dirname($media->getStorageDirectory().$media->getPartitionDirectory()).'/test.txt');
    $storageMock->put(dirname($media->getStorageDirectory().$media->getPartitionDirectory(), 2).'/test.txt', 'test');

    expect($media->addFromFile(__DIR__.'/../Fixtures/test.png')->deleteFile())->toBeNull();

    $storageMock->delete(dirname($media->getStorageDirectory().$media->getPartitionDirectory(), 2).'/test.txt');
    expect($media->addFromFile(__DIR__.'/../Fixtures/test.png')->deleteFile())->toBeNull();
    $storageMock->assertMissing($media->getStorageDirectory().'/'.$media->getPartitionDirectory().'/'.$media->name);
});

it('throws exception when disk name is not configured', function() {
    config(['igniter-system.assets.attachment.disk' => 'invalid']);
    $media = new Media;
    $media->disk = 'public';
    expect($media->getDiskDriverName())->toBe(config('filesystems.disks.public.driver'));

    $media->disk = null;
    expect(fn() => $media->getDiskName())->toThrow(LogicException::class, 'Disk invalid is not configured.');
});
