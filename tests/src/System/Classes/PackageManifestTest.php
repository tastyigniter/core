<?php

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Classes\PackageManifest;

it('returns all packages from manifest', function() {
    $expected = ['package1', 'package2'];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('getRequire')->andReturn($expected);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    expect($manifest->packages())->toBe($expected);
});

it('returns all extensions from manifest', function() {
    $expected = [
        ['type' => 'tastyigniter-extension'],
        ['type' => 'tastyigniter-theme'],
    ];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('getRequire')->andReturn($expected);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    expect($manifest->extensions())->toBe([['type' => 'tastyigniter-extension']]);
});

it('returns all themes from manifest', function() {
    $expected = [
        ['type' => 'tastyigniter-extension'],
        ['type' => 'tastyigniter-theme'],
    ];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('getRequire')->andReturn($expected);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    expect($manifest->themes())->toBe([['type' => 'tastyigniter-theme']]);
});

it('returns correct package path for relative path', function() {
    $manifest = resolve(PackageManifest::class);
    $manifest->vendorPath = '/vendor';

    $result = $manifest->getPackagePath('../path/to/package');
    expect($result)->toBe('/vendor/composer/../path/to/package');
});

it('returns correct package path for absolute path', function() {
    $manifest = resolve(PackageManifest::class);

    $result = $manifest->getPackagePath('/path/to/package');
    expect($result)->toBe('/path/to/package');
});

it('returns version for given package code', function() {
    $expected = [
        ['code' => 'package1', 'version' => '1.0.0'],
        ['code' => 'package2', 'version' => '2.0.0'],
    ];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('getRequire')->andReturn($expected);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    expect($manifest->getVersion('package1'))->toBe('1.0.0');
});

it('returns core version from installed packages', function() {
    $expected = [
        'packages' => [
            ['name' => 'tastyigniter/core', 'version' => '1.0.0'],
        ],
    ];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnTrue();
    $filesystem->shouldReceive('get')->andReturn(json_encode($expected));
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());
    $manifest->vendorPath = '/vendor';

    expect($manifest->coreVersion())->toBe('1.0.0');
});

it('builds manifest with extensions and themes', function() {
    $filesystem = mock(Filesystem::class);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());
    $filesystem->shouldReceive('exists')->andReturnTrue();
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'packages' => [
            [
                'name' => 'tastyigniter/ti-ext-sample',
                'extra' => ['tastyigniter-extension' => ['code' => 'sample']],
                'install-path' => '/path/to/sample',
            ],
            [
                'name' => 'tastyigniter/ti-theme-sample',
                'extra' => ['tastyigniter-theme' => ['code' => 'sample']],
                'install-path' => '/path/to/sample',
            ],
            [
                'name' => 'other/package',
                'extra' => [],
                'install-path' => '/path/to/other',
            ],
        ],
    ]));
    $filesystem->shouldReceive('replace')->once();

    $manifest->build();
});

it('returns core addons from composer.json', function() {
    $expected = [
        'tastyigniter/ti-ext-sample' => ['code' => 'igniter.sample', 'version' => '1.0.0'],
        'tastyigniter/ti-theme-sample' => ['code' => 'igniter.sample', 'version' => '1.0.0'],
    ];
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode([
        'require' => [
            'tastyigniter/ti-ext-sample' => '1.0.0',
            'tastyigniter/ti-theme-sample' => '1.0.0',
            'other/package' => '1.0.0',
        ],
    ]));
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    $result = $manifest->coreAddons();

    expect($result)->toBe($expected)
        ->and($result)->toBe($manifest->coreAddons());
});

it('returns empty array if no disabled addons file exists', function() {
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode([]));
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());
    $manifest->manifestPath = '/path/to/manifest';

    expect($manifest->disabledAddons())->toBe([]);
});

it('returns disabled addons from file', function() {
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('get')->andReturn(json_encode(['addon1', 'addon2']));
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());

    expect($manifest->disabledAddons())->toBe(['addon1', 'addon2']);
});

it('writes disabled addons to file', function() {
    $filesystem = mock(Filesystem::class);
    $manifest = new PackageManifest($filesystem, $this->app->basePath(), Igniter::getCachedAddonsPath());
    $manifest->manifestPath = '/path/to/manifest';
    $filesystem->shouldReceive('replace')->with('/path/to/disabled-addons.json', json_encode(['addon1', 'addon2']));
    $filesystem->shouldReceive('get')->andReturn(json_encode(['addon1', 'addon2']));

    $manifest->writeDisabled(['addon1', 'addon2']);

    $manifest->manifestPath = Igniter::getCachedAddonsPath();

    expect($manifest->disabledAddons())->toBe(['addon1', 'addon2']);
});
