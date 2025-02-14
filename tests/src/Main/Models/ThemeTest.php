<?php

namespace Igniter\Tests\Main\Models;

use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Main\Classes\Theme as ThemeData;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;

it('creates a new theme instance for a given theme data', function() {
    $themeData = new ThemeData(__DIR__, ['code' => 'tests-new-theme', 'name' => 'New Theme']);
    $theme = Theme::forTheme($themeData);
    expect($theme->code)->toBe('tests-new-theme');
});

it('returns true when onboarding is complete', function() {
    Theme::clearDefaultModel();
    $theme = Theme::factory()->create([
        'code' => 'tests-theme',
        'name' => 'Test Theme',
        'data' => ['key' => 'value'],
        'status' => 1,
    ]);
    $theme->makeDefault();

    expect(Theme::onboardingIsComplete())->toBeTrue();
});

it('returns layout options for the theme', function() {
    $themeData = new ThemeData(testThemePath(), ['code' => 'tests-theme']);
    $theme = Theme::factory()->make(['code' => 'tests-theme']);
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;

    expect($theme->getLayoutOptions())->toHaveKey('default', 'default [default]');
});

it('returns component options', function() {
    expect(Theme::getComponentOptions())->toHaveKeys([
        'testComponent',
        'testComponentWithLifecycle',
        'test::livewire-component',
    ]);
});

it('returns default theme name when not set', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    expect($theme->name)->toBe('Tests Theme');
});

it('returns theme description', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    expect($theme->description)->toBe('A Test theme for TastyIgniter front-end');
});

it('returns theme locked mode', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'tests-theme', 'author' => 'Test Author', 'locked' => true]);
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;

    expect($theme->locked)->toBeTrue();
});

it('returns default version when not set', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    expect($theme->version)->toBe('0.1.0');
});

it('returns theme author', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'tests-theme', 'author' => 'Test Author']);
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;

    expect($theme->author)->toBe('Test Author');
});

it('returns theme screenshot', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'tests-theme', 'author' => 'Test Author']);
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;

    expect($theme->screenshot)->toBe('');
});

it('returns false when activating non existence theme', function() {
    expect(Theme::activateTheme('non-existence'))->toBeFalse();
});

it('returns fields config from theme form config', function() {
    $theme = new Theme(['code' => 'tests-theme']);
    $themeData = new ThemeData(testThemePath(), [
        'code' => 'tests-theme',
        'form' => [
            'general' => [
                'title' => 'General',
                'fields' => [
                    'field1' => [
                        'label' => 'Field 1',
                    ],
                ],
            ],
        ],
    ]);
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;

    expect($theme->getFieldsConfig())->toHaveKey('field1')
        ->and($theme->getFieldsConfig())->toHaveKey('field1');
});

it('returns field values when data is set', function() {
    $theme = new Theme(['data' => ['field1' => 'value1']]);
    expect($theme->getFieldValues())->toBe(['field1' => 'value1']);
});

it('saves theme customizer attributes', function() {
    Theme::flushEventListeners();
    $theme = Theme::factory()->make(['code' => 'tests-theme-suffix', 'name' => 'Test Theme']);
    $theme->name = 'New Test Theme';
    $theme->background_color = '#ffffff';
    $theme->save();
    $theme = $theme->fresh();

    expect($theme->data)->toHaveKey('background_color', '#ffffff');
});

it('activates a theme and installs required extensions', function() {
    $theme = Theme::factory()->create(['code' => 'tests-theme', 'name' => 'Test Theme', 'status' => 1]);
    $themeData = mock(ThemeData::class);
    $themeData->shouldReceive('listRequires')->andReturn(['extension1' => '1.0.0']);
    $themeData->shouldReceive('hasParent')->andReturnFalse();
    $themeData->shouldReceive('getMetaPath')->andReturn('/path/to/theme/meta');
    resolve(ThemeManager::class)->themes['tests-theme'] = $themeData;
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    $extensionManager->shouldReceive('getExtensions')->andReturn([]);
    $extensionManager->shouldReceive('hasExtension')->with('extension1')->andReturn(true);
    $extensionManager->shouldReceive('installExtension')->with('extension1');

    expect(Theme::activateTheme('tests-theme')->getKey())->toBe($theme->getKey());
});

it('generates unique theme code', function() {
    Theme::factory()->make(['code' => 'tests-theme-suffix', 'name' => 'Test Theme']);

    $themeCode = Theme::generateUniqueCode('tests-theme', 'suffix');
    expect($themeCode)->toStartWith('tests-theme-');
});

it('skips non existence theme when syncing all', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('paths')->andReturn([
        'non-existence' => '/path/to/non-existence',
        'invalid-code' => '/path/to/invalid-code',
    ]);
    $themeManager->shouldReceive('findTheme')->with('non-existence')->andReturnNull();
    $themeManager->shouldReceive('findTheme')->with('invalid-code')->andReturn(new ThemeData(testThemePath(), ['code' => 'invalidd-code']));

    expect(Theme::syncAll())->toBeNull();
});

it('configures theme model correctly', function() {
    $theme = new Theme;

    expect(class_uses_recursive($theme))
        ->toContain(Defaultable::class)
        ->toContain(Purgeable::class)
        ->toContain(Switchable::class)
        ->and(Theme::ICON_MIMETYPES)->toEqual([
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
        ])
        ->and($theme->getTable())->toBe('themes')
        ->and($theme->getKeyName())->toBe('theme_id')
        ->and($theme->getFillable())->toEqual([
            'theme_id',
            'name',
            'code',
            'version',
            'description',
            'data',
            'status',
            'is_default',
        ])
        ->and($theme->getCasts())->toEqual([
            'theme_id' => 'int',
            'data' => 'array',
            'status' => 'boolean',
            'is_default' => 'boolean',
        ])
        ->and($theme->getPurgeableAttributes())->toEqual([
            'template', 'settings', 'markup', 'codeSection',
        ])
        ->and($theme->timestamps)->toBeTrue();
});
