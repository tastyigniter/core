<?php

namespace Igniter\Tests\Flame\Pagic\Parsers;

use Igniter\Flame\Pagic\Cache\FileSystem;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Template\Code\PageCode;
use Igniter\Main\Template\Layout;
use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Cache;

it('loads template object correctly', function() {
    $layout = Layout::load('tests-theme', 'default');
    $page = Page::load('tests-theme', 'nested-page');

    $fileParser = FileParser::on($page);
    $pageCode = $fileParser->source($page, $layout, controller());

    expect($pageCode)->toEqual($fileParser->source($page, $layout, controller()));
});

it('handles valid cache and returns object', function() {
    $model = mock(Model::class)->makePartial();
    $filePath = 'path/to/file.blade.php';
    $className = 'Pagic'.str_replace('.', '', uniqid('', true)).'_'.md5(mt_rand()).'Class';
    $model->shouldReceive('getFilePath')->andReturn($filePath);
    $model->mTime = 1000;

    Cache::shouldReceive('get')->with('php-file-data', false)->andReturn(base64_encode(serialize([
        $filePath => ['className' => $className, 'mTime' => 1000],
    ])));
    File::partialMock()->shouldReceive('exists')->andReturnFalse();
    $fileCache = mock(FileSystem::class)->makePartial();
    $fileCache->shouldReceive('getCacheKey')->andReturn($filePath);
    app()->instance(FileSystem::class, $fileCache);

    File::shouldReceive('isFile')->with($filePath)->andReturn(true);
    File::shouldReceive('get')->andReturn('<?php class '.$className.' extends \Igniter\Main\Template\Code\PageCode {}');
    File::shouldReceive('delete')->andReturn(true);
    File::shouldReceive('put')->andReturn(true);
    File::shouldReceive('move')->andReturn(true);

    $parser = FileParser::on($model);
    expect(fn() => $parser->source($model, $model, controller()))->toThrow('Class "'.$className.'" not found');
});

it('returns template object when source is valid', function() {
    $model = mock(Model::class)->makePartial();
    $filePath = 'path/to/file.blade.php';
    $templateCode = '<?php use Igniter\Main\Helpers\MainHelper; function test() {}';
    $model->shouldReceive('getFilePath')->andReturn($filePath);
    $model->shouldReceive('getCodeClassParent')->andReturn(PageCode::class);
    $model->mTime = 1000;
    $model->code = $templateCode;

    $fileCache = mock(FileSystem::class);
    $fileCache->shouldReceive('getCacheKey')->andReturn($filePath);
    $fileCache->shouldReceive('getCached')->andReturnNull();
    $fileCache->shouldReceive('load')->andReturn(true);
    $fileCache->shouldReceive('storeCached')->andReturn(true);
    $fileCache->shouldReceive('write')->andReturn(true)->byDefault();
    app()->instance(FileSystem::class, $fileCache);

    File::shouldReceive('lastModified')->andReturn(100);
    File::shouldReceive('isFile')->with($filePath)->andReturn(true);

    expect(FileParser::on($model)->source($model, $model, controller()))->toBeInstanceOf(PageCode::class);
});

it('handles corrupt cache and returns object', function() {
    $model = mock(Model::class)->makePartial();
    $filePath = 'path/to/file.blade.php';
    $className = 'Pagic'.str_replace('.', '', uniqid('', true)).'_'.md5(mt_rand()).'Class';
    $fileContents = '<?php class '.$className.' extends \Igniter\Main\Template\Code\PageCode {}';
    $model->shouldReceive('getFilePath')->andReturn($filePath);
    $model->shouldReceive('getCodeClassParent')->andReturn('ParentClass');
    $model->mTime = 1000;

    $fileCache = mock(FileSystem::class);
    $fileCache->shouldReceive('getCacheKey')->andReturn($filePath);
    $fileCache->shouldReceive('getCached')->andReturn(['className' => 'NonExistentClass', 'mTime' => 1000]);
    $fileCache->shouldReceive('load')->andReturn(true);
    $fileCache->shouldReceive('storeCached')->andReturn(true);
    $fileCache->shouldReceive('write')->andReturn(true);
    app()->instance(FileSystem::class, $fileCache);

    File::shouldReceive('isFile')->with($filePath)->andReturn(true);
    File::shouldReceive('get')->andReturn($fileContents);
    eval('?>'.$fileContents);

    $result = FileParser::on($model)->source($model, $model, controller());
    expect($result)->toBeInstanceOf($className);
});

it('returns null when extracting class name from file', function() {
    $model = mock(Model::class)->makePartial();
    $filePath = 'path/to/file.blade.php';
    $model->shouldReceive('getFilePath')->andReturn($filePath);
    $model->shouldReceive('getCodeClassParent')->andReturn('ParentClass');
    $model->mTime = 1000;

    $fileCache = mock(FileSystem::class);
    $fileCache->shouldReceive('getCacheKey')->andReturn($filePath);
    $fileCache->shouldReceive('getCached')->andReturn(['className' => 'PagicInvalidClassNameClass', 'mTime' => 1000]);
    $fileCache->shouldReceive('load')->andReturn(true);
    $fileCache->shouldReceive('storeCached')->andReturn(true);
    $fileCache->shouldReceive('write')->andReturn(true);
    app()->instance(FileSystem::class, $fileCache);

    File::shouldReceive('isFile')->with($filePath)->andReturn(true);
    File::shouldReceive('get')->andReturn('<?php class PagicInvalidClassNameClass extends \Igniter\Main\Template\Code\PageCode {}');
    File::shouldReceive('delete')->andReturn(true);

    expect(fn() => FileParser::on($model)->source($model, $model, controller()))->toThrow('Class "PagicInvalidClassNameClass" not found');
});

it('throws exception when can not write compiled file', function() {
    config(['igniter-pagic.forceBytecodeInvalidation' => true]);
    $model = mock(Model::class)->makePartial();
    $filePath = 'path/to/file.blade.php';
    $templateCode = '<?php use Igniter\Main\Helpers\MainHelper; function test() {}';
    $model->shouldReceive('getFilePath')->andReturn($filePath);
    $model->shouldReceive('getCodeClassParent')->andReturn(PageCode::class);
    $model->mTime = 1000;
    $model->code = $templateCode;

    $fileCache = mock(FileSystem::class)->makePartial();
    $fileCache->shouldReceive('getCacheKey')->andReturn($filePath);
    $fileCache->shouldReceive('getCached')->andReturnNull();
    $fileCache->shouldReceive('load')->andReturn(true);
    $fileCache->shouldReceive('storeCached')->andReturn(true);
    app()->instance(FileSystem::class, $fileCache);

    File::shouldReceive('lastModified')->andReturn(100);
    File::shouldReceive('isFile')->with($filePath)->andReturn(true);
    File::shouldReceive('isDirectory')->andReturn(false, true);
    File::shouldReceive('makeDirectory')->andReturnFalse();
    File::shouldReceive('chmod')->andReturn(true);

    expect(fn() => FileParser::on($model)->source($model, $model, controller()))
        ->toThrow(fn(\RuntimeException $e) => str_contains($e->getMessage(), 'Unable to create cache directory'));

    File::shouldReceive('put')->withArgs(function($tmpFile, $content) {
        @unlink($tmpFile);
        return true;
    })->andReturn(false, true);
    expect(fn() => FileParser::on($model)->source($model, $model, controller()))
        ->toThrow(fn(\RuntimeException $e) => str_contains($e->getMessage(), 'Failed to write cache file '));

    File::shouldReceive('move')->withArgs(function($tmpFile, $content) {
        @unlink($tmpFile);
        return true;
    })->andReturn(false, true);
    expect(fn() => FileParser::on($model)->source($model, $model, controller()))
        ->toThrow(fn(\RuntimeException $e) => str_contains($e->getMessage(), 'Failed to write cache file '));

    File::shouldReceive('chmod')->andReturnTrue();
    expect(FileParser::on($model)->source($model, $model, controller()))->toBeInstanceOf(PageCode::class);
});

namespace Igniter\Flame\Pagic\Cache;

function function_exists($function)
{
    return in_array($function, ['opcache_invalidate', 'apc_compile_file']);
}

function opcache_invalidate($function)
{
    return false;
}

function apc_compile_file($function)
{
    return false;
}

