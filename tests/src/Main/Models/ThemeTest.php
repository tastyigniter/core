<?php

namespace Igniter\Tests\Main\Models;

use Igniter\Main\Classes\Theme as ThemeData;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\ExtensionManager;

it('creates a new theme instance for a given theme data', function() {
    $themeData = new ThemeData(__DIR__, ['code' => 'test-theme']);
    $theme = Theme::forTheme($themeData);
    expect($theme->code)->toBe('test-theme');
});

it('returns false when onboarding is not complete', function() {
    Theme::truncate();
    expect(Theme::onboardingIsComplete())->toBeFalse();
});

it('returns true when onboarding is complete', function() {
    $theme = Theme::create(['code' => 'test-theme', 'name' => 'Test Theme', 'data' => ['key' => 'value'], 'status' => 1]);
    $theme->makeDefault();
    Theme::clearDefaultModel();

    expect(Theme::onboardingIsComplete())->toBeTrue();
});

it('returns layout options for the theme', function() {
    $themeData = new ThemeData(testThemePath(), ['code' => 'test-theme']);
    $theme = Theme::create(['code' => 'test-theme']);
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;

    expect($theme->getLayoutOptions())->toBe([]);
});

it('returns component options', function() {
    expect(Theme::getComponentOptions())->toHaveKeys([
        'testComponent',
        'testComponentWithLifecycle',
        'test::livewire-component',
    ]);
});

it('returns default theme name when not set', function() {
    $theme = new Theme(['code' => 'test-theme', 'name' => 'Test Theme']);
    expect($theme->name)->toBe('Test Theme');
});

it('returns theme description', function() {
    $theme = new Theme(['code' => 'test-theme', 'description' => 'Test Description']);
    expect($theme->description)->toBe('Test Description');
});

it('returns theme locked mode', function() {
    $theme = new Theme(['code' => 'test-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'test-theme', 'author' => 'Test Author', 'locked' => true]);
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;

    expect($theme->locked)->toBeTrue();
});

it('returns default version when not set', function() {
    $theme = new Theme(['code' => 'test-theme']);
    expect($theme->version)->toBe('0.1.0');
});

it('returns theme author', function() {
    $theme = new Theme(['code' => 'test-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'test-theme', 'author' => 'Test Author']);
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;

    expect($theme->author)->toBe('Test Author');
});

it('returns theme screenshot', function() {
    $theme = new Theme(['code' => 'test-theme']);
    $themeData = new ThemeData(testThemePath(), ['code' => 'test-theme', 'author' => 'Test Author']);
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;

    expect($theme->screenshot)->toBe('');
});

it('returns false when activating non existence theme', function() {
    expect(Theme::activateTheme('non-existence'))->toBeFalse();
});

it('returns fields config from theme form config', function() {
    $theme = new Theme(['code' => 'test-theme']);
    $themeData = new ThemeData(testThemePath(), [
        'code' => 'test-theme',
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
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;

    expect($theme->getFieldsConfig())->toHaveKey('field1')
        ->and($theme->getFieldsConfig())->toHaveKey('field1');
});

it('returns field values when data is set', function() {
    $theme = new Theme(['data' => ['field1' => 'value1']]);
    expect($theme->getFieldValues())->toBe(['field1' => 'value1']);
});

it('saves theme customizer attributes', function() {
    $theme = Theme::create(['code' => 'test-theme-suffix', 'name' => 'Test Theme']);
    $theme->name = 'New Test Theme';
    $theme->background_color = '#ffffff';
    $theme->save();
    $theme = $theme->fresh();

    expect($theme->data)->toHaveKey('background_color', '#ffffff');
})->skip();

it('activates a theme and installs required extensions', function() {
    $theme = Theme::create(['code' => 'test-theme', 'name' => 'Test Theme', 'status' => 1]);
    $themeData = mock(ThemeData::class);
    $themeData->shouldReceive('listRequires')->andReturn(['extension1' => '1.0.0']);
    $themeData->shouldReceive('hasParent')->andReturnFalse();
    $themeData->shouldReceive('getMetaPath')->andReturn('/path/to/theme/meta');
    resolve(ThemeManager::class)->themes['test-theme'] = $themeData;
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    $extensionManager->shouldReceive('getExtensions')->andReturn([]);
    $extensionManager->shouldReceive('hasExtension')->with('extension1')->andReturn(true);
    $extensionManager->shouldReceive('installExtension')->with('extension1');

    expect(Theme::activateTheme('test-theme')->getKey())->toBe($theme->getKey());
});

it('generates unique theme code', function() {
    Theme::create(['code' => 'test-theme-suffix', 'name' => 'Test Theme']);

    $themeCode = Theme::generateUniqueCode('test-theme', 'suffix');
    expect($themeCode)->toStartWith('test-theme-');
});
