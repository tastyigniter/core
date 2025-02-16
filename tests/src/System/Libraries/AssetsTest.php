<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Libraries;

use Igniter\Admin\ServiceProvider;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Facades\Assets as AssetsFacade;
use Igniter\System\Libraries\Assets;

it('adds assets from admin manifest successfully', function() {
    Igniter::shouldReceive('runningInAdmin')->andReturnTrue();
    Igniter::shouldReceive('loadControllersFrom')->once();
    Igniter::shouldReceive('adminUri')->andReturn('admin');
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('themesPath')->andReturn(base_path('themes'));
    $adminServiceProvider = new ServiceProvider(app());
    $adminServiceProvider->register();

    expect(AssetsFacade::getCss())->toContain('rel="stylesheet" type="text/css"')
        ->and(AssetsFacade::getJs())->toContain('charset="utf-8" type="text/javascript"')
        ->and(AssetsFacade::getFavIcon())->toContain('rel="shortcut icon" type="image/x-icon"')
        ->and(AssetsFacade::getMetas())->toContain('name="Content-type" content="text/html; charset=utf-8" type="equiv"')
        ->and(AssetsFacade::getRss())->toBeNull();
});

it('adds assets from theme manifest successfully', function() {
    $theme = resolve(ThemeManager::class)->findTheme('igniter-orange');

    $assets = new Assets;
    $assets->addAssetsFromThemeManifest($theme);

    expect($assets->getCss())->toContain('rel="stylesheet" type="text/css"')
        ->and($assets->getJs())->toContain('charset="utf-8" type="text/javascript"');
});

it('returns null when adding assets from non existence manifest', function() {
    $assets = new Assets;
    $assets->addFromManifest('/non/existence/path');

    expect($assets->getCss())->toBeNull()
        ->and($assets->getJs())->toBeNull()
        ->and($assets->getFavIcon())->toBeNull()
        ->and($assets->getMetas())->toBeNull()
        ->and($assets->getRss())->toBeNull()
        ->and($assets->getJsVars())->toBe('');
});

it('adds favicon successfully', function() {
    $assets = new Assets;
    $assets->addFavIcon(['href' => 'favicon.ico']);
    $assets->addFavIcon(['href' => public_path('favicon.ico')]);

    expect($assets->getFavIcon())->toContain('favicon.ico');
});

it('adds rss successfully', function() {
    $assets = new Assets;
    $assets->addTag('rss', 'https://example.com/rss.xml');

    expect($assets->getRss())->toContain('https://example.com/rss.xml');
});

it('adds and retrieves js variables successfully', function() {
    $assets = new Assets;
    $assets->putJsVars([
        'key' => 'value',
        'object' => (object)['key' => 'value'],
        'toJson' => new class
        {
            public function toJson(): array
            {
                return ['key' => 'value'];
            }
        },
        'toString' => new class
        {
            public function __toString()
            {
                return 'value';
            }
        },
    ]);
    $assets->mergeJsVars('key', ['new-value']);

    $jsVars = $assets->getJsVars();

    expect($jsVars)->toContain('key')
        ->and($jsVars)->toContain('value');
});

it('throws exception when invalid transforming js variable', function() {
    $assets = new Assets;
    $assets->putJsVars([
        'key' => new class {},
    ]);

    expect(fn() => $assets->getJsVars())
        ->toThrow(new \RuntimeException('Cannot transform this object to JavaScript.'));
});

it('removes duplicate assets from combined', function() {
    File::put(public_path('/test.css'), 'body { color: red; }');

    $assets = new Assets;
    $assets->addTags([
        'css' => [
            [
                'path' => base_path('css/style.css'),
                'rel' => 'stylesheet',
                'type' => 'text/css',
            ],
            [
                'path' => 'css/style.css',
                'rel' => 'stylesheet',
                'type' => 'text/css',
            ],
            [
                'path' => 'css/style.css',
                'rel' => 'stylesheet',
                'type' => 'text/css',
            ],
            [
                'path' => public_path('/test.css'),
                'rel' => 'stylesheet',
                'type' => 'text/css',
            ],
        ],
    ]);
    $assets->registerSourcePath(__DIR__.'/../../../resources');

    expect($assets->getCss())->toContain('rel="stylesheet" type="text/css"');
});
