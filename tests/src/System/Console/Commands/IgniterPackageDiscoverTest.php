<?php

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\System\Classes\PackageManifest;

it('rebuilds cached addons manifest successfully', function() {
    $packageManifest = mock(PackageManifest::class);
    app()->instance(PackageManifest::class, $packageManifest);
    $filesystem = mock(Filesystem::class);
    $packageManifest->files = $filesystem;
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('delete')->andReturnNull();
    $packageManifest->shouldReceive('build')->andReturnSelf();
    $packageManifest->shouldReceive('packages')->andReturn([
        ['code' => 'igniter.demo', 'name' => 'Demo'],
        ['code' => 'igniter.blog', 'name' => 'Blog'],
    ]);

    $this->artisan('igniter:package-discover')
        ->assertExitCode(0);
});

it('skips deletion if manifest file does not exist', function() {
    $packageManifest = mock(PackageManifest::class);
    app()->instance(PackageManifest::class, $packageManifest);
    $filesystem = mock(Filesystem::class);
    $packageManifest->files = $filesystem;
    $filesystem->shouldReceive('exists')->andReturn(false);
    $filesystem->shouldReceive('delete')->never();
    $packageManifest->shouldReceive('build')->andReturnSelf();
    $packageManifest->shouldReceive('packages')->andReturn([
        ['code' => 'igniter.demo', 'name' => 'Demo'],
        ['code' => 'igniter.blog', 'name' => 'Blog'],
    ]);

    $this->artisan('igniter:package-discover')
        ->assertExitCode(0);
});
