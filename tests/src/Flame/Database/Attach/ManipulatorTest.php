<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Attach;

use Igniter\Flame\Database\Attach\Manipulator;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use LogicException;

it('throws exception for unsupported driver', function() {
    $manipulator = new Manipulator('path/to/file.jpg');
    expect(fn() => $manipulator->useDriver('unsupported'))->toThrow(LogicException::class);
});

it('throws exception when manipulating local file', function() {
    $manipulator = new Manipulator(base_path('file.jpg'));
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn(mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');
    expect(fn() => $manipulator->manipulate([
        'width' => 100,
    ]))->toThrow(InvalidArgumentException::class,
        'The provided path ('.base_path('file.jpg').') must be a relative path to the file, from the source root',
    );
});

it('manipulates file correctly', function() {
    $manipulator = Manipulator::make('path/to/file.jpg');
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn($s3Driver = mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');
    $s3Driver->shouldReceive('fileExists')->andReturnTrue();
    $s3Driver->shouldReceive('read')->andReturn(Manipulator::decodedBlankImage());
    $source->shouldReceive('put')->withSomeOfArgs('path/to/attachments/file.jpg')->once();

    $manipulator->manipulate([
        'width' => 100,
        'height' => 100,
        'crop' => true,
        'watermark' => 'path/to/watermark.png',
    ]);
    $manipulator->save('path/to/attachments/file.jpg');
});

it('manipulates file correctly using local filesystem', function() {
    $manipulator = Manipulator::make('path/to/file.jpg');
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn($localDriver = mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');
    $localDriver->shouldReceive('fileExists')->andReturnTrue();
    $localDriver->shouldReceive('read')->andReturn(Manipulator::decodedBlankImage());
    File::shouldReceive('copy')->once();

    $manipulator->manipulate([
        'width' => 100,
        'height' => 100,
        'crop' => true,
    ]);
    $manipulator->save(base_path('/attachments/file.jpg'));
});

it('saves files without manipulations', function() {
    $manipulator = Manipulator::make('path/to/file.jpg');
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn($s3Driver = mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');
    $s3Driver->shouldReceive('fileExists')->andReturnTrue();
    $s3Driver->shouldReceive('read')->andReturn(Manipulator::decodedBlankImage());
    File::shouldReceive('copy')->once();

    $manipulator->save(base_path('/attachments/file.jpg'));
});

it('saves files without manipulations using local filesystem', function() {
    $manipulator = Manipulator::make('path/to/file.jpg');
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn($localDriver = mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');
    $localDriver->shouldReceive('fileExists')->andReturnTrue();
    $localDriver->shouldReceive('read')->andReturn(Manipulator::decodedBlankImage());
    $source->shouldReceive('copy')->once();

    $manipulator->save('path/to/attachments/file.jpg');
});

it('checks if file is supported', function() {
    $manipulator = new Manipulator('path/to/file.jpg');
    expect($manipulator->isSupported())->toBeTrue();
});

it('returns false for unsupported file', function() {
    $manipulator = new Manipulator('path/to/file.txt');
    expect($manipulator->isSupported())->toBeFalse();

    $manipulator = new Manipulator('path/to/file.txt');
    $manipulator->useDriver('imagick');

    expect($manipulator->isSupported())->toBeFalse();

    $manipulator = new Manipulator('path/to/file');
    expect($manipulator->isSupported())->toBeFalse();
});

it('throws exception for invalid manipulation', function() {
    $manipulator = new Manipulator('path/to/file.jpg');
    $manipulator->useSource($source = mock(Filesystem::class));
    $source->shouldReceive('getDriver')->andReturn(mock(FilesystemOperator::class));
    $source->shouldReceive('path')->andReturn('/path');

    expect(fn() => $manipulator->manipulate([
        'invalid' => 'value',
    ]))->toThrow(InvalidArgumentException::class, "Unknown parameter 'invalid' provided when manipulating 'path/to/file.jpg'");
});
