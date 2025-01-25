<?php

namespace Igniter\Tests\System\Helpers;

use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Helpers\CacheHelper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

it('clears all caches successfully', function() {
    $cacheHelper = new CacheHelper();
    Cache::shouldReceive('flush')->once();
    File::shouldReceive('glob')->andReturn([]);
    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('directories')->andReturn([]);
    File::shouldReceive('delete')->andReturn(true);
    File::shouldReceive('deleteDirectory')->andReturn(true);

    $cacheHelper->clear();
});

it('clears view cache successfully', function() {
    $cacheHelper = new CacheHelper();
    $path = config('view.compiled');
    File::shouldReceive('glob')->with("{$path}/*")->andReturn(['view1.php', 'view2.php']);
    File::shouldReceive('delete')->with('view1.php')->once();
    File::shouldReceive('delete')->with('view2.php')->once();

    $cacheHelper->clearView();
});

it('clears combiner cache successfully', function() {
    $cacheHelper = new CacheHelper();
    File::shouldReceive('isDirectory')->with(storage_path().'/igniter/combiner')->andReturn(true);
    File::shouldReceive('directories')->with(storage_path().'/igniter/combiner')->andReturn(['dir1', 'dir2']);
    File::shouldReceive('deleteDirectory')->with('dir1')->once();
    File::shouldReceive('deleteDirectory')->with('dir2')->once();

    $cacheHelper->clearCombiner();
});

it('clears cache directory successfully', function() {
    $cacheHelper = new CacheHelper();
    $path = config('igniter-pagic.parsedTemplateCachePath', storage_path('/igniter/cache'));
    File::shouldReceive('isDirectory')->with($path)->andReturn(true);
    File::shouldReceive('directories')->with($path)->andReturn(['dir1', 'dir2']);
    File::shouldReceive('deleteDirectory')->with('dir1')->once();
    File::shouldReceive('deleteDirectory')->with('dir2')->once();

    $cacheHelper->clearCache();
});

it('does not clear when directory does not exists', function() {
    config(['igniter-pagic.parsedTemplateCachePath' => storage_path('/non_existent')]);
    $cacheHelper = new CacheHelper();
    File::shouldReceive('isDirectory')->with(storage_path().'/non_existent')->once()->andReturn(false);

    $cacheHelper->clearCache();
});

it('clears compiled files successfully', function() {
    $cacheHelper = new CacheHelper();
    File::shouldReceive('delete')->with(Igniter::getCachedAddonsPath())->once();
    File::shouldReceive('delete')->with(App::getCachedPackagesPath())->once();
    File::shouldReceive('delete')->with(App::getCachedServicesPath())->once();

    $cacheHelper->clearCompiled();
});

it('does not clear non-existent directory', function() {
    $cacheHelper = new CacheHelper();
    File::shouldReceive('isDirectory')->with(storage_path().'/non_existent')->once()->andReturn(false);

    $cacheHelper->clearDirectory('/non_existent');
});
