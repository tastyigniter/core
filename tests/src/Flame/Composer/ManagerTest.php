<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Composer\Autoload\ClassLoader;
use Igniter\Flame\Composer\Manager;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Output\OutputInterface;

it('loads package version correctly', function() {
    $version = resolve(Manager::class)->getPackageVersion('some-package');

    expect($version)->toBeNull();
});

it('loads package name correctly', function() {
    $name = resolve(Manager::class)->getPackageName('some-package');
    expect($name)->toBeNull();
});

it('lists installed packages correctly', function() {
    $composer = mock(Composer::class);
    $manager = new Manager('/root', '/storage', $composer);
    File::shouldReceive('exists')->with('/root/vendor/composer/installed.json')->andReturnTrue();
    File::shouldReceive('get')->with('/root/vendor/composer/installed.json')->andReturn(json_encode([
        'packages' => [
            [
                'name' => 'author/package',
                'type' => 'tastyigniter-package',
                'version' => '1.0.0',
                'extra' => [
                    'tastyigniter-package' => [
                        'code' => 'author.package',
                        'homepage' => 'https://example.com',
                    ],
                ],
            ],
            [
                'name' => 'author/extension',
                'type' => 'tastyigniter-extension',
                'version' => '1.0.0',
                'extra' => [
                    'tastyigniter-extension' => [
                        'code' => 'author.extension',
                        'homepage' => 'https://example.com',
                    ],
                ],
            ],
            [
                'name' => 'author/theme',
                'type' => 'tastyigniter-theme',
                'version' => '1.0.0',
                'extra' => [
                    'tastyigniter-theme' => [
                        'code' => 'author.theme',
                        'homepage' => 'https://example.com',
                    ],
                ],
            ],
            [
                'name' => 'author/library',
                'type' => 'library',
                'version' => '1.0.0',
            ],
        ],
    ]));

    $packages = $manager->listInstalledPackages();

    expect($packages->count())->toBe(3)
        ->and($packages->all())->toHaveKeys(['author.package', 'author.extension', 'author.theme']);
});

it('returns loader when autoload file exists', function() {
    $composer = mock(Composer::class);
    $loader = (new Manager(__DIR__.'/../../../../', '/storage', $composer))->getLoader();

    expect($loader)->toBeInstanceOf(ClassLoader::class);
});

it('formats extension manifest correctly', function() {
    File::shouldReceive('json')->with('/path/to/extension/composer.json')->andReturn([
        'name' => 'author/extension',
        'description' => 'Some description',
        'authors' => [
            [
                'name' => 'Author Name',
                'email' => 'author@example.com',
            ],
        ],
        'autoload' => [
            'psr-4' => [
                'Author\\Extension\\' => 'src/',
            ],
        ],
        'extra' => [
            'tastyigniter-extension' => [
                'code' => 'author.extension',
                'homepage' => 'https://example.com',
            ],
        ],
    ]);
    $manifest = resolve(Manager::class)->getExtensionManifest('/path/to/extension');

    expect($manifest)->toBeArray()
        ->and($manifest['type'])->toBe('tastyigniter-extension')
        ->and($manifest['code'])->toBe('author.extension')
        ->and($manifest['package_name'])->toBe('author/extension');
});

it('formats theme manifest correctly', function() {
    File::shouldReceive('json')->with('/path/to/theme/composer.json')->andReturn([
        'name' => 'author/theme',
        'description' => 'Some description',
        'authors' => [
            [
                'name' => 'Author Name',
                'email' => 'author@example.com',
            ],
        ],
        'autoload' => [
            'psr-4' => [
                'Author\\Theme\\' => 'src/',
            ],
        ],
        'extra' => [
            'tastyigniter-theme' => [
                'code' => 'theme-code',
                'homepage' => 'https://example.com',
            ],
        ],
    ]);

    $manifest = resolve(Manager::class)->getThemeManifest('/path/to/theme');

    expect($manifest)->toBeArray()
        ->and($manifest['type'])->toBe('tastyigniter-theme')
        ->and($manifest['code'])->toBe('theme-code')
        ->and($manifest['package_name'])->toBe('author/theme');
});

it('does not format theme with missing extra config', function() {
    File::shouldReceive('json')->with('/path/to/theme/composer.json')->andReturn([
        'name' => 'author/theme',
        'description' => 'Some description',
        'authors' => [
            [
                'name' => 'Author Name',
                'email' => 'author@example.com',
            ],
        ],
        'autoload' => [
            'psr-4' => [
                'Author\\Theme\\' => 'src/',
            ],
        ],
    ]);

    $manifest = resolve(Manager::class)->getThemeManifest('/path/to/theme');

    expect($manifest)->toBeArray()->toBeEmpty();
});

it('installs packages correctly', function() {
    $requirements = [
        'package/name' => '^1.0',
        'package/another' => '^2.0',
    ];
    $output = mock(OutputInterface::class);
    $composer = mock(Composer::class);
    File::shouldReceive('isDirectory')->with('/storage/backups')->andReturnFalse();
    File::shouldReceive('makeDirectory')->with('/storage/backups', null, true);
    File::shouldReceive('copy')->with(base_path('composer.json'), '/storage/backups/composer.json');
    File::shouldReceive('isFile')->with(base_path('composer.json'))->andReturnTrue();
    File::shouldReceive('isFile')->with(base_path('composer.lock'))->andReturnTrue();
    File::shouldReceive('copy')->with(base_path('composer.lock'), '/storage/backups/composer.lock');
    $composer->shouldReceive('requirePackages')->with([
        'package/name' => '^1.0',
        'package/another' => '^2.0',
    ], false, $output)->once();

    (new Manager(base_path(), '/storage', $composer))->install($requirements, $output);
});

it('restores composer files on exception in install', function() {
    $requirements = ['package/name' => '^1.0'];
    $output = mock(OutputInterface::class);
    $composer = mock(Composer::class);
    File::shouldReceive('isDirectory')->with('/storage/backups')->andReturnFalse();
    File::shouldReceive('makeDirectory')->with('/storage/backups', null, true);
    File::shouldReceive('copy')->with(base_path('composer.json'), '/storage/backups/composer.json');
    File::shouldReceive('isFile')->with(base_path('composer.json'))->andReturnTrue();
    File::shouldReceive('isFile')->with(base_path('composer.lock'))->andReturnTrue();
    File::shouldReceive('copy')->with(base_path('composer.lock'), '/storage/backups/composer.lock');
    $composer->shouldReceive('requirePackages')->andThrow(new \Exception('Error'));
    File::shouldReceive('copy')->with('/storage/backups/composer.json', base_path('composer.json'));
    File::shouldReceive('isFile')->with('/storage/backups/composer.lock')->andReturnTrue();
    File::shouldReceive('copy')->with('/storage/backups/composer.lock', base_path('composer.lock'));

    $manager = new Manager(base_path(), '/storage', $composer);
    expect(fn() => $manager->install($requirements, $output))->toThrow(\Exception::class);
});

it('uninstalls packages correctly', function() {
    $packages = ['package/name', 'package/another'];
    $output = mock(OutputInterface::class);
    $composer = mock(Composer::class);
    File::shouldReceive('isDirectory')->with('/storage/backups')->andReturnFalse();
    File::shouldReceive('makeDirectory')->with('/storage/backups', null, true);
    File::shouldReceive('copy')->with(base_path('composer.json'), '/storage/backups/composer.json');
    File::shouldReceive('isFile')->with(base_path('composer.json'))->andReturnTrue();
    File::shouldReceive('isFile')->with(base_path('composer.lock'))->andReturnTrue();
    File::shouldReceive('copy')->with(base_path('composer.lock'), '/storage/backups/composer.lock');
    $composer->shouldReceive('removePackages')->with($packages, false, $output)->once();

    (new Manager(base_path(), '/storage', $composer))->uninstall($packages, $output);
});

it('restores composer files on exception in uninstall', function() {
    $packages = ['package/name', 'package/another'];
    $output = mock(OutputInterface::class);
    $composer = mock(Composer::class);
    File::shouldReceive('isDirectory')->with('/storage/backups')->andReturnFalse();
    File::shouldReceive('makeDirectory')->with('/storage/backups', null, true);
    File::shouldReceive('copy')->with(base_path('composer.json'), '/storage/backups/composer.json');
    File::shouldReceive('isFile')->with(base_path('composer.json'))->andReturnTrue();
    File::shouldReceive('isFile')->with(base_path('composer.lock'))->andReturnTrue();
    File::shouldReceive('copy')->with(base_path('composer.lock'), '/storage/backups/composer.lock');
    $composer->shouldReceive('removePackages')->andThrow(new \Exception('Error'));
    File::shouldReceive('copy')->with('/storage/backups/composer.json', base_path('composer.json'));
    File::shouldReceive('isFile')->with('/storage/backups/composer.lock')->andReturnTrue();
    File::shouldReceive('copy')->with('/storage/backups/composer.lock', base_path('composer.lock'));

    $manager = new Manager(base_path(), '/storage', $composer);
    expect(fn() => $manager->uninstall($packages, $output))->toThrow(\Exception::class);
});

it('adds auth credentials to config', function() {
    file_put_contents(base_path('auth.json'), '{}');
    $manager = new Manager(base_path(), '/storage');
    $manager->addAuthCredentials('username', 'password');

    $config = json_decode(file_get_contents(base_path('auth.json')), true);
    expect($config['http-basic']['satis.tastyigniter.com']['username'])->toBe('username')
        ->and($config['http-basic']['satis.tastyigniter.com']['password'])->toBe('password');

    unlink(base_path('auth.json'));
});

it('modifies composer config with new repository', function() {
    file_put_contents(__DIR__.'/composer.json', '{}');
    $manager = new Manager(__DIR__, '/storage', new Composer(new Filesystem, __DIR__));
    $manager->assertSchema();

    $config = json_decode(file_get_contents(__DIR__.'/composer.json'), true);
    expect($config['repositories'])->toBeArray()
        ->and($config['repositories'][0]['type'])->toBe('composer')
        ->and($config['repositories'][0]['url'])->toBe('https://satis.tastyigniter.com');

    unlink(__DIR__.'/composer.json');
});

it('does not modify composer config if repository exists', function() {
    file_put_contents(__DIR__.'/composer.json', json_encode([
        'repositories' => [
            ['type' => 'composer', 'url' => 'https://satis.tastyigniter.com'],
            ['type' => 'composer', 'url' => 'https://packagist.org'],
        ],
    ]));
    $manager = new Manager(__DIR__, '/storage', new Composer(new Filesystem, __DIR__));
    $manager->assertSchema();

    $config = json_decode(file_get_contents(__DIR__.'/composer.json'), true);
    expect($config['repositories'])->toBeArray()
        ->and($config['repositories'][0]['type'])->toBe('composer')
        ->and($config['repositories'][0]['url'])->toBe('https://satis.tastyigniter.com')
        ->and($config['repositories'][1]['type'])->toBe('composer')
        ->and($config['repositories'][1]['url'])->toBe('https://packagist.org');

    unlink(__DIR__.'/composer.json');
});
