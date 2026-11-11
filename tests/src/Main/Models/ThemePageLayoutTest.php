<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Models;

use Igniter\Main\Models\ThemePageLayout;

it('finds the saved layout for a theme page', function() {
    $layout = ThemePageLayout::create([
        'theme_code' => 'tests-theme',
        'page_code' => 'home',
        'sections' => [['id' => 's_1', 'layout' => 'cols-1', 'columns' => []]],
    ]);

    $found = ThemePageLayout::findFor('tests-theme', 'home');

    expect($found->getKey())->toBe($layout->getKey())
        ->and($found->sections)->toBe([['id' => 's_1', 'layout' => 'cols-1', 'columns' => []]])
        ->and(ThemePageLayout::findFor('tests-theme', 'missing'))->toBeNull()
        ->and(ThemePageLayout::findFor('other-theme', 'home'))->toBeNull();
});
