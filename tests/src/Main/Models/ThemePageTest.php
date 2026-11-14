<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Models;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\ThemePage;

it('finds the saved layout for a theme page', function() {
    $layout = ThemePage::create([
        'theme_code' => 'tests-theme',
        'page_code' => 'home',
        'sections' => [['id' => 's_1', 'layout' => 'cols-1', 'columns' => []]],
    ]);

    $found = ThemePage::findFor('tests-theme', 'home');

    expect($found->getKey())->toBe($layout->getKey())
        ->and($found->sections)->toBe([['id' => 's_1', 'layout' => 'cols-1', 'columns' => []]])
        ->and(ThemePage::findFor('tests-theme', 'missing'))->toBeNull()
        ->and(ThemePage::findFor('other-theme', 'home'))->toBeNull();
});

it("saves and resolves page settings, with a child theme taking its parent's", function() {
    ThemePage::saveSettings('tests-theme', 'home', ['title' => 'Home', 'description' => 'Parent']);
    ThemePage::create(['theme_code' => 'tests-theme', 'page_code' => 'about', 'sections' => []]);
    ThemePage::saveSettings('tests-theme', 'about', ['title' => 'About']);

    expect(ThemePage::settingsFor('tests-theme', 'home'))->toBe(['title' => 'Home', 'description' => 'Parent'])
        ->and(ThemePage::settingsFor('tests-theme', 'missing'))->toBe([])
        ->and(ThemePage::findFor('tests-theme', 'about')->sections)->toBe([]);

    $child = mock(Theme::class)->makePartial();
    $child->shouldReceive('getParentName')->andReturn('tests-theme');
    resolve(ThemeManager::class)->themes['tests-child'] = $child;
    ThemePage::saveSettings('tests-child', 'home', ['title' => 'Child home']);

    expect(ThemePage::resolveSettings('tests-child', 'home'))->toBe(['title' => 'Child home', 'description' => 'Parent']);

    // Empty settings remove a settings-only row, and keep a row that has sections.
    ThemePage::saveSettings('tests-theme', 'home', []);
    ThemePage::saveSettings('tests-theme', 'about', []);

    expect(ThemePage::findFor('tests-theme', 'home'))->toBeNull()
        ->and(ThemePage::findFor('tests-theme', 'about')->settings)->toBeNull()
        ->and(ThemePage::settingsFor('tests-theme', 'about'))->toBe([]);

    unset(resolve(ThemeManager::class)->themes['tests-child']);
    ThemePage::clearSettingsCache();
});
