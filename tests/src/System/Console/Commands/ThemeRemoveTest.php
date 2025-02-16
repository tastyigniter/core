<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Main\Classes\ThemeManager;

it('removes theme successfully', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('hasTheme')->with('tests-theme')->andReturnTrue();
    $themeManager->shouldReceive('deleteTheme')->with('tests-theme')->once();

    $this->artisan('igniter:theme-remove tests-theme')
        ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
        ->expectsOutput('Removing theme: tests-theme')
        ->expectsOutput('Deleted theme: tests-theme')
        ->assertExitCode(0);
});

it('handles theme not found', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('hasTheme')->with('tests-theme')->andReturnFalse();

    $this->artisan('igniter:theme-remove tests-theme')
        ->expectsOutput('Unable to find a registered theme called "tests-theme"')
        ->assertExitCode(0);
});

it('skips removal if not confirmed', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('hasTheme')->with('tests-theme')->andReturnTrue();
    $themeManager->shouldReceive('deleteTheme')->never();

    $this->artisan('igniter:theme-remove tests-theme')
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);
});

it('handles exception during removal', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('hasTheme')->with('tests-theme')->andReturnTrue();
    $themeManager->shouldReceive('deleteTheme')->with('tests-theme')->andThrow(new \Exception('An error occurred'));

    $this->artisan('igniter:theme-remove tests-theme')
        ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
        ->expectsOutput('An error occurred')
        ->assertExitCode(0);
});
