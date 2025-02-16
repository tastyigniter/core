<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;

it('publishes assets for all themes successfully', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('loadThemes');
    $themeManager->shouldReceive('listThemes')->andReturn([$theme = mock(Theme::class)]);
    $theme->shouldReceive('getPathsToPublish')->andReturn([
        'path/from/assets' => '/path/to/assets',
    ]);

    $this->artisan('igniter:theme-vendor-publish')
        ->expectsOutput('Publishing theme assets...')
        ->expectsOutput('Publishing complete.')
        ->assertExitCode(0);
});

it('publishes assets for a specific theme successfully', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('loadThemes');
    $themeManager->shouldReceive('findTheme')->andReturn($theme = mock(Theme::class));
    $theme->shouldReceive('getPathsToPublish')->andReturn([
        'path/from/assets' => '/path/to/assets',
    ]);

    $this->artisan('igniter:theme-vendor-publish', ['--theme' => 'demo'])
        ->expectsOutput('Publishing theme assets...')
        ->expectsOutput('Publishing complete.')
        ->assertExitCode(0);
});

it('handles no publishable resources for a theme', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('loadThemes');
    $themeManager->shouldReceive('listThemes')->andReturn([$theme = mock(Theme::class)]);
    $theme->shouldReceive('getPathsToPublish')->andReturn([]);
    $theme->shouldReceive('getName')->andReturn('tests-theme');

    $this->artisan('igniter:theme-vendor-publish')
        ->expectsOutput('Publishing theme assets...')
        ->expectsOutput('No publishable resources for theme [tests-theme].')
        ->expectsOutput('Publishing complete.')
        ->assertExitCode(0);
});
