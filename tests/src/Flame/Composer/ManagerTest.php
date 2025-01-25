<?php

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Composer\Manager;

it('loads package version correctly', function() {
    $version = resolve(Manager::class)->getPackageVersion('some-package');

    expect($version)->toBeNull();
});

it('loads package name correctly', function() {
    $name = resolve(Manager::class)->getPackageName('some-package');
    expect($name)->toBeNull();
});

it('lists installed packages correctly', function() {
    $manager = resolve(Manager::class);

    $packages = $manager->listInstalledPackages();

    expect($packages)->toBeCollection()
        ->and($packages->isEmpty())->toBeFalse();
});

it('formats extension manifest correctly', function() {
    $manager = resolve(Manager::class);

    $manifest = $manager->getExtensionManifest('/path/to/extension');

    expect($manifest)->toBeArray()
        ->and($manifest['type'])->toBe('tastyigniter-extension');
});

it('formats theme manifest correctly', function() {
    $manager = resolve(Manager::class);

    $manifest = $manager->getThemeManifest('/path/to/theme');

    expect($manifest)->toBeArray()
        ->and($manifest['type'])->toBe('tastyigniter-theme');
});

it('adds repository to composer.json repositories config', function() {})->skip();

it('removes repository config from composer.json repositories config', function() {})->skip();

it('checks composer.json repositories config has a hostname', function() {})->skip();

it('loads required repository and auth config', function() {})->skip();

it('requires core package', function() {})->skip();

it('requires package', function() {})->skip();

it('updates package', function() {})->skip();

it('removes package', function() {})->skip();
