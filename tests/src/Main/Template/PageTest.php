<?php

namespace Igniter\Tests\Main\Template;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Code\PageCode;
use Igniter\Main\Template\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;

it('initializes correctly', function() {
    expect(Page::DIR_NAME)->toBe('_pages');
});

it('returns correct URL for a given page', function() {
    expect(Page::url('components'))->toBe('http://localhost/components');
});

it('returns null for non-theme-page menu type', function() {
    expect(Page::getMenuTypeInfo('non-theme-page'))->toBeNull();
});

it('returns null when no active theme', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('getActiveThemeCode')->andReturnNull();
    expect(Page::getMenuTypeInfo('theme-page'))->toBeNull();
});

it('returns menu type info for theme-page', function() {
    expect(Page::getMenuTypeInfo('theme-page')['references'])
        ->toHaveKey('components', 'Components [components]');
});

it('resolves menu item with correct URL and active status', function() {
    $item = (object)['reference' => 'components'];
    $theme = new Theme(testThemePath(), ['code' => 'tests-theme']);
    expect(Page::resolveMenuItem($item, 'http://localhost/components', $theme))->toBe([
        'url' => 'http://localhost/components',
        'isActive' => true,
    ]);
});

it('returns null if menu item reference is empty', function() {
    $item = (object)['reference' => ''];
    $theme = new Theme(testThemePath(), ['code' => 'tests-theme']);

    $result = Page::resolveMenuItem($item, '/home', $theme);
    expect($result)->toBeNull();
});

it('returns correct code class parent', function() {
    expect((new Page)->getCodeClassParent())->toBe(PageCode::class);
});

it('resolves route binding through events and returns page', function() {
    $page = mock(Page::class);

    Event::listen('main.page.beforeRoute', function($pageCode) use ($page) {
        return $page;
    });

    expect(Page::resolveRouteBinding('event-page'))->toBe($page);
});

it('resolves route binding and returns page', function() {
    expect(Page::resolveRouteBinding('components'))->toBeInstanceOf(Page::class);
});

it('throws ModelNotFoundException if page is not found', function() {
    expect(fn() => Page::resolveRouteBinding('non-existence'))->toThrow(ModelNotFoundException::class);
});

it('throws ModelNotFoundException if page is hidden and user is not admin', function() {
    expect(fn() => Page::resolveRouteBinding('hidden-page'))->toThrow(ModelNotFoundException::class);
});
