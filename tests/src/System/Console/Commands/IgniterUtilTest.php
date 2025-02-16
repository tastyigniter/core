<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Facades\Assets;

it('errors when utility command method is not defined', function() {
    $this->artisan('igniter:util unknown')
        ->expectsOutput('Utility command "unknown" does not exist!')
        ->assertExitCode(0);
});

it('executes set version command successfully', function() {
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('version')->andReturn('2.0.0');
    $packageManifest = mock(PackageManifest::class);
    app()->instance(PackageManifest::class, $packageManifest);
    $packageManifest->shouldReceive('build')->andReturnSelf();
    $packageManifest->shouldReceive('packages')->andReturn([
        ['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'tastyigniter-extension', 'version' => '2.0.0'],
        ['code' => 'igniter.blog', 'name' => 'Orange', 'type' => 'tastyigniter-theme', 'version' => '2.0.0'],
    ]);

    $this->artisan('igniter:util set version --extensions')
        ->expectsOutput('Setting TastyIgniter version number...')
        ->expectsOutput('*** TastyIgniter latest version: 2.0.0')
        ->expectsOutput('*** igniter.demo latest version: 2.0.0')
        ->expectsOutput('*** igniter.blog latest version: 2.0.0')
        ->assertExitCode(0);
});

it('skips set version command if no database', function() {
    Igniter::shouldReceive('hasDatabase')->andReturnFalse();

    $this->artisan('igniter:util set version')
        ->expectsOutput('Setting TastyIgniter version number...')
        ->expectsOutput('Skipping - No database detected.')
        ->assertExitCode(0);
});

it('compiles scss successfully', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturn(new Theme('demo'));
    Assets::shouldReceive('buildBundles')->andReturn([
        'Bundled assets compiled successfully',
    ]);

    $this->artisan('igniter:util compile scss')
        ->expectsOutput('Compiling registered asset bundles...')
        ->expectsOutput('Bundled assets compiled successfully')
        ->assertExitCode(0);
});

it('skips compiling scss if no active theme', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturnNull();

    $this->artisan('igniter:util compile scss')
        ->expectsOutput('Compiling registered asset bundles...')
        ->expectsOutput('Nothing to compile!')
        ->assertExitCode(0);
});

it('sets carte successfully', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('applySiteDetail')->with('carteKey')->once();

    $this->artisan('igniter:util set carte --carteKey=carteKey')
        ->expectsOutput('Setting Carte Key...')
        ->assertExitCode(0);
});

it('skips setting carte if no key defined', function() {
    $this->artisan('igniter:util set carte')
        ->expectsOutput('Setting Carte Key...')
        ->expectsOutput('No carteKey defined, use --key=<key> to set a Carte')
        ->assertExitCode(0);
});

it('sets theme successfully', function() {
    $theme = ThemeModel::create([
        'name' => 'Orange',
        'code' => 'igniter-orange',
        'version' => '1.0.0',
        'data' => [],
        'status' => 1,
    ]);

    $this->artisan('igniter:util set theme --theme='.$theme->code)
        ->expectsOutput('Theme ['.$theme->name.'] set as default')
        ->assertExitCode(0);

    expect(ThemeModel::where('code', $theme->code)->first()->is_default)->toBeTrue();
    ThemeModel::clearDefaultModel();
});

it('skips setting theme if no theme defined', function() {
    $this->artisan('igniter:util set theme')
        ->expectsOutput('No theme defined, use --theme=<code> to set a theme')
        ->assertExitCode(0);
});
