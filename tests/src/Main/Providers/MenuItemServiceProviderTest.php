<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Providers;

use Igniter\Main\Classes\Theme;
use Illuminate\Support\Facades\Event;

it('returns theme page type in menu item list types', function() {
    $result = Event::dispatch('pages.menuitem.listTypes');

    expect(array_merge(...$result))->toHaveKey('theme-page', 'igniter::main.pages.text_theme_page');
});

it('returns menu item type info', function() {
    config(['igniter-pagic.parsedTemplateCacheTTL' => null]);
    $result = Event::dispatch('pages.menuitem.getTypeInfo', ['theme-page']);

    $references = array_get(array_merge(...array_filter($result)), 'references');

    expect($references)->toHaveKey('components', 'Components [components]')
        ->and($references)->toHaveKey('nested-page', 'Nested page [nested-page]');
});

it('resolves theme page menu item', function() {
    $item = (object)['type' => 'theme-page', 'reference' => 'test-page'];
    $url = 'http://localhost';
    $theme = new Theme(testThemePath(), ['code' => 'tests-theme']);

    $result = Event::dispatch('pages.menuitem.resolveItem', [$item, $url, $theme]);

    expect(array_merge(...array_filter($result)))->toHaveKey('url', 'http://localhost/test-page');
});
