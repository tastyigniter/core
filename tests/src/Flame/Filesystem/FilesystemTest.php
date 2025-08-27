<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Filesystem;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\Facades\Igniter;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ReflectionClass;

beforeEach(function () {
    $this->filesystem = new Filesystem;
});

it('symbolizes path', function () {
    Igniter::loadResourcesFrom(__DIR__.'/../../../resources/themes/tests-theme', 'tests.fixtures');

    $path = resolve(Filesystem::class)->symbolizePath('tests.fixtures::theme.json');

    expect($path)->toEndWith('resources/themes/tests-theme/theme.json');
});

it('checks directory is empty', function () {
    expect($this->filesystem->isDirectoryEmpty(storage_path('/').uniqid('temp').'-unreadable_directory'))->toBeTrue();

    $directory = storage_path('/').uniqid('temp').'-empty_directory';
    mkdir($directory);
    expect($this->filesystem->isDirectoryEmpty($directory))->toBeTrue();
    rmdir($directory);

    expect($this->filesystem->isDirectoryEmpty(__DIR__.'/fixtures/test_directory'))->toBeFalse();
});

it('converts bytes to human readable format', function () {
    expect($this->filesystem->sizeToString(1073741824))->toBe('1.00 GB')
        ->and($this->filesystem->sizeToString(1048576))->toBe('1.00 MB')
        ->and($this->filesystem->sizeToString(1024))->toBe('1.00 KB')
        ->and($this->filesystem->sizeToString(10))->toBe('10 bytes')
        ->and($this->filesystem->sizeToString(1))->toBe('1 byte')
        ->and($this->filesystem->sizeToString(0))->toBe('0 bytes');
});

it('returns public path from absolute path', function () {
    $publicPath = public_path('welcome');
    expect($this->filesystem->localToPublic($publicPath))->toBe('/welcome');
});

it('checks path is within application path', function () {
    $file = base_path('composer.json');
    expect($this->filesystem->isLocalPath($file))->toBeTrue()
        ->and($this->filesystem->isLocalPath('/some/external/directory'))->toBeFalse();
});

it('checks if disk is using local driver', function () {
    $adapter = mock(LocalFilesystemAdapter::class);
    $disk = mock(FilesystemAdapter::class);
    $disk->shouldReceive('getAdapter')->andReturn($adapter);
    expect($this->filesystem->isLocalDisk($disk))->toBeTrue();
});

it('returns file path from class name', function () {
    $className = Filesystem::class;
    $reflector = new ReflectionClass($className);
    expect($this->filesystem->fromClass($className))->toBe($reflector->getFileName());
});

it('checks file exist case insensitively', function () {
    $filesystem = mock(Filesystem::class)->makePartial();
    $filesystem->shouldReceive('exists')->andReturn(false, false, false, true);
    $filesystem->shouldReceive('glob')->andReturn(false, ['test_directory/file.txt']);
    expect($filesystem->existsInsensitive('/non_existent_file.txt'))->toBeFalse()
        ->and($filesystem->existsInsensitive('/non_existent_file.txt'))->toBeFalse()
        ->and($filesystem->existsInsensitive('test_directory/FILE.txt'))->toBe('test_directory/file.txt')
        ->and($filesystem->existsInsensitive('test_directory/file.txt'))->toBe('test_directory/file.txt');
});

it('returns normalized path', function () {
    $path = 'some\\windows\\path';
    expect($this->filesystem->normalizePath($path))->toBe('some/windows/path');
});

it('returns symbolized path', function () {
    $this->filesystem->addPathSymbol('@', base_path('modules'));
    $path = '@::some_module';
    expect($this->filesystem->symbolizePath($path, false, false))->toBe(base_path('modules/some_module'))
        ->and($this->filesystem->symbolizePath(base_path('modules/some_module')))->toBe(base_path('modules/some_module'))
        ->and($this->filesystem->isPathSymbol($path))->toBe('@')
        ->and($this->filesystem->isPathSymbol('some/regular/path'))->toBeFalse()
        ->and($this->filesystem->pathSymbols['@'])->toContain(base_path('modules'));
});

it('writes contents to a file and sets permissions', function () {
    $path = storage_path('/').uniqid('temp').'-'.fake()->lexify('???.txt');
    @unlink($path);
    $contents = 'test contents';
    $this->filesystem->put($path, $contents);
    expect(file_get_contents($path))->toBe($contents);
    unlink($path);
});

it('copies a file to a new location and sets permissions', function () {
    $source = storage_path('/').uniqid('temp').'-'.fake()->lexify('???.txt');
    $target = storage_path('/').uniqid('temp').'-'.fake()->lexify('???.txt');
    file_put_contents($source, 'test contents');
    expect($this->filesystem->copy($source, $target))->toBeTrue()
        ->and(file_get_contents($target))->toBe('test contents');
    unlink($source);
    unlink($target);
});

it('creates a directory and sets permissions', function () {
    $path = storage_path('/').uniqid('temp').'-'.fake()->lexify('???');
    $subPath = $path.'/'.fake()->lexify('???');
    $filesystem = mock(Filesystem::class)->makePartial();
    $filesystem->folderPermissions = '0755';
    $filesystem->shouldReceive('chmod')->twice()->andReturn();
    $filesystem->shouldReceive('chmodRecursive')->once()->andReturn();
    $filesystem->makeDirectory($path);
    $filesystem->makeDirectory($subPath2 = $subPath.'/'.fake()->lexify('???'), 0755, true);

    expect(is_dir($path))->toBeTrue();
    rmdir($subPath2);
    rmdir($subPath);
    rmdir($path);
});

it('modifies file permissions', function () {
    $path = storage_path('/').uniqid('temp').'-'.fake()->lexify('???.txt');
    $filesystem = mock(Filesystem::class)->makePartial();
    $filesystem->shouldReceive('isDirectory')->times(3)->andReturn(true, false);
    expect($filesystem->chmod($path))->toBeFalse(); // file permission is not set

    $filesystem->filePermissions = '0644';
    $filesystem->folderPermissions = '0777';
    expect($filesystem->chmod($path))->toBeBool(); // file permission is set

    $directory = storage_path('/').uniqid('temp').'-test_directory';
    $filesystem->shouldReceive('dirname')->once()->andReturn($directory);
    $filesystem->makeDirectory($directory, 0755, true, true);
    rmdir($directory);
});

it('modifies directory permissions recursively', function () {
    $path = __DIR__.'/fixtures';
    $filesystem = mock(Filesystem::class)->makePartial();
    expect($filesystem->chmodRecursive($path))->toBeFalse();

    $filesystem->filePermissions = '0777';
    $filesystem->folderPermissions = '0777';
    $filesystem->shouldReceive('isDirectory')->andReturn(false, true);
    expect($filesystem->chmodRecursive($path))->toBeFalse()
        ->and($filesystem->chmodRecursive($path))->toBeNull();
});

it('returns default file permission mask', function () {
    $this->filesystem->filePermissions = '0644';
    expect($this->filesystem->getFilePermissions())->toBe(0644);
});

it('returns default folder permission mask', function () {
    $this->filesystem->folderPermissions = '0755';
    expect($this->filesystem->getFolderPermissions())->toBe(0755);
});

it('matches filename against pattern', function () {
    $fileName = 'test_file.txt';
    $pattern = '*.txt';
    expect($this->filesystem->fileNameMatch($fileName, $fileName))->toBeTrue()
        ->and($this->filesystem->fileNameMatch($fileName, $pattern))->toBeTrue();
});

namespace Igniter\Flame\Filesystem;

function chmod(string $filename, int $permissions): bool
{
    return true;
}
