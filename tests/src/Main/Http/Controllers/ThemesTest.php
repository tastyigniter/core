<?php

namespace Igniter\Tests\Main\Http\Controllers;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\System\Facades\Assets;

it('loads themes index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes'))
        ->assertStatus(200);
});

it('loads customise theme page', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme']);
    $theme = resolve(ThemeManager::class)->findTheme('tests-theme');
    $theme->locked = true;

    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'edit/tests-theme']))
        ->assertStatus(200);
});

it('loads edit theme template file page', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'data' => ['field1' => 'value1']]);
    $theme = resolve(ThemeManager::class)->findTheme('tests-theme');
    $theme->locked = true;

    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'source/tests-theme']))
        ->assertStatus(200);
});

it('redirects when unable to delete an active theme', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'status' => 1, 'is_default' => 1]);

    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'delete/tests-theme']))
        ->assertRedirect();
});

it('deletes and redirect when theme exists in database and not filesystem', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturnNull();
    ThemeModel::create(['code' => 'no-theme', 'name' => 'Tests Theme']);

    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'delete/no-theme']))
        ->assertRedirect();
});

it('loads delete theme page', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme']);

    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'delete/tests-theme']))
        ->assertStatus(200);
});

it('sets default theme correctly', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'status' => 1]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes'), ['code' => 'tests-theme'], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSetDefault',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    expect(ThemeModel::where('is_default', 1)->first()->code)->toBe('tests-theme');
});

it('resets theme customisation settings', function() {
    config(['igniter-system.buildThemeAssetsBundle' => true]);
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'data' => ['field1' => 'value1']]);
    Assets::partialMock()->shouldReceive('buildBundles')->once();

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'edit/tests-theme']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    expect(ThemeModel::where('code', 'tests-theme')->first()->data)->toBe([]);
});

it('edits theme template file content', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'status' => 1]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'source/tests-theme']), ['markup' => 'new content'], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertSee('X_IGNITER_REDIRECT');
});

it('creates child theme', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'status' => 1]);
    $childTheme = ThemeModel::create(['code' => 'child-theme', 'name' => 'Child Theme', 'status' => 1]);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('createChildTheme')->andReturn($childTheme);
    $themeManager->shouldReceive('paths')->andReturn(['child-theme' => testThemePath()]);
    $themeManager->shouldReceive('findTheme')->andReturn(new Theme(__DIR__));

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'source/tests-theme']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onCreateChild',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    expect(ThemeModel::where('is_default', 1)->first()->code)->toBe('child-theme');
});

it('redirects when deleting an active theme', function() {
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme', 'status' => 1, 'is_default' => 1]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'delete/tests-theme']), ['delete_data' => 1], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertSee('X_IGNITER_REDIRECT');
});

it('deletes theme correctly', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('deleteTheme')->once();
    ThemeModel::create(['code' => 'tests-theme', 'name' => 'Tests Theme']);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'delete/tests-theme']), ['delete_data' => 1], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertSee('X_IGNITER_REDIRECT');
});
