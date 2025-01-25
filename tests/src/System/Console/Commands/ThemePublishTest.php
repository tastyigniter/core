<?php

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;

it('publishes theme files successfully', function() {
    $activeTheme = resolve(ThemeManager::class)->getActiveTheme();
    $activeTheme->path = theme_path().'/demo';
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('publishableThemeFiles')->andReturn([
        'assets' => '/assets',
    ]);

    $this->artisan('igniter:theme-publish')
        ->expectsOutput('Publishing theme assets...')
        ->expectsOutput('Publishing complete.')
        ->assertExitCode(0);
});

it('skips publishing if no publishable files', function() {
    $activeTheme = resolve(ThemeManager::class)->getActiveTheme();
    $activeTheme->path = theme_path().'/demo';
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('publishableThemeFiles')->andReturn([]);

    $this->artisan('igniter:theme-publish')
        ->expectsOutput('Publishing theme assets...')
        ->expectsOutput('No publishable custom files for theme [tests-theme].')
        ->assertExitCode(0);
});

it('throws exception if no active theme', function() {
    config(['igniter-system.defaultTheme' => 'demo']);

    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('publishableThemeFiles')->andReturn([]);

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(lang('igniter::admin.alert_error_nothing'));

    $this->artisan('igniter:theme-publish');
});

it('throws exception if theme is locked', function() {
    $activeTheme = resolve(ThemeManager::class)->getActiveTheme();
    $activeTheme->locked = true;
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('publishableThemeFiles')->andReturn([]);

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(lang('igniter::system.themes.alert_theme_locked'));

    $this->artisan('igniter:theme-publish');
});

it('throws exception if theme path is invalid', function() {
    $activeTheme = resolve(ThemeManager::class)->getActiveTheme();
    $activeTheme->path = '/invalid/path';
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    Igniter::shouldReceive('publishableThemeFiles')->andReturn([]);

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(lang('igniter::system.themes.alert_no_publish_custom'));

    $this->artisan('igniter:theme-publish');
});
