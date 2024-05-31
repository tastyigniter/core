<?php

namespace Igniter\Tests\Igniter\Main\Template;

use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;

it('reads page settings from blueprint.json', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'nested-page');

    expect($page->settings['title'])->toBe('Nested page');
})->skip('Undefined key title');
