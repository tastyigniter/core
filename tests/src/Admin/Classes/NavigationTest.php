<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\Navigation;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Mockery;

beforeEach(function() {
    $this->navigation = new Navigation;
});

it('constructs correctly', function() {
    $navigation = new Navigation('test/path');

    expect($navigation->viewPath)->toBe(['test/path']);
});

it('sets context with item code only', function() {
    $navigation = new Navigation();
    $navigation->setContext('settings', 'system');

    expect($navigation->isActiveNavItem('invalid'))->toBeFalse()
        ->and($navigation->isActiveNavItem('settings'))->toBeTrue();
});

it('sets context with item code and parent code', function() {
    $navigation = new Navigation();
    $navigation->setContext('settings', 'system');

    expect($navigation->isActiveNavItem('system'))->toBeTrue();
});

it('registers a navigation items', function() {
    $this->navigation->registerNavItems([
        'test' => [
            'code' => 'test',
            'title' => 'Test',
            'class' => 'test',
            'icon' => 'fa fa-angle-double-right',
            'href' => 'http://localhost/admin/test',
            'priority' => 0,
            'permission' => ['Admin.Test'],
        ],
    ]);

    $items = $this->navigation->getNavItems();

    expect($items['test']['code'])->toBe('test')
        ->and($items['test']['class'])->toBe('test')
        ->and($items['test']['href'])->toBe('http://localhost/admin/test')
        ->and($items['test']['icon'])->toBe('fa fa-angle-double-right')
        ->and($items['test']['title'])->toBe('Test')
        ->and($items['test']['priority'])->toBe(0)
        ->and($items['test']['permission'])->toBe(['Admin.Test'])
        ->and($items['test']['child'])->toBeNull();
});

it('registers a navigation item', function() {
    $this->navigation->registerNavItem('test', [
        'code' => 'test',
        'title' => 'Test',
        'class' => 'test',
        'icon' => 'fa fa-angle-double-right',
        'href' => 'http://localhost/admin/test',
        'priority' => 90,
        'permission' => ['Admin.Test'],
    ]);

    $items = $this->navigation->getNavItems();

    expect($items['test']['code'])->toBe('test')
        ->and($items['test']['class'])->toBe('test')
        ->and($items['test']['href'])->toBe('http://localhost/admin/test')
        ->and($items['test']['icon'])->toBe('fa fa-angle-double-right')
        ->and($items['test']['title'])->toBe('Test')
        ->and($items['test']['priority'])->toBe(90)
        ->and($items['test']['permission'])->toBe(['Admin.Test']);
});

it('registers a navigation child item', function() {
    $this->navigation->registerNavItem('test', [
        'code' => 'test',
        'title' => 'Test',
        'class' => 'test',
        'icon' => 'fa fa-angle-double-right',
        'href' => 'http://localhost/admin/test',
        'priority' => 90,
        'permission' => ['Admin.Test'],
    ], 'parentItem');

    $items = $this->navigation->getNavItems();
    $navItem = $items['parentItem']['child']['test'];

    expect($navItem['code'])->toBe('test')
        ->and($navItem['class'])->toBe('test')
        ->and($navItem['href'])->toBe('http://localhost/admin/test')
        ->and($navItem['icon'])->toBe('fa fa-angle-double-right')
        ->and($navItem['title'])->toBe('Test')
        ->and($navItem['priority'])->toBe(90)
        ->and($navItem['permission'])->toBe(['Admin.Test']);
});

it('loads registered admin navigation items', function() {
    $items = AdminMenu::getNavItems();

    expect($items)
        ->toHaveKeys([
            'dashboard',
            'restaurant',
            'marketing',
            'design.child.themes',
            'tools.child.media_manager',
            'system.child.settings',
        ])
        ->and(AdminMenu::loadItems())->toBeNull()
        ->and($items['dashboard']['code'])->toBe('dashboard')
        ->and($items['dashboard']['class'])->toBe('dashboard admin')
        ->and($items['dashboard']['href'])->toBe('http://localhost/admin/dashboard')
        ->and($items['dashboard']['icon'])->toBe('fa-tachometer-alt')
        ->and($items['dashboard']['title'])->toBe('Dashboard')
        ->and($items['dashboard']['priority'])->toBe(0)
        ->and($items['dashboard']['permission'])->toBeNull()
        ->and($items['dashboard']['child'])->toBeNull();
});

it('loads registered admin main menu items', function() {
    $items = AdminMenu::getMainItems();

    expect($items)
        ->toHaveKeys(['preview', 'help', 'settings'])
        ->and($items['settings'])->toHaveProperties([
            'itemName', 'type', 'disabled', 'context', 'icon', 'attributes', 'priority', 'permission',
        ]);
});

it('adds and gets navigation items correctly', function() {
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
    ]);

    $items = $this->navigation->getNavItems();

    expect($items['testItem']['code'])->toBe('testItem')
        ->and($items['testItem']['class'])->toBe('testClass')
        ->and($items['testItem']['href'])->toBe('http://localhost/admin/testItem')
        ->and($items['testItem']['icon'])->toBe('fa fa-angle-double-right')
        ->and($items['testItem']['title'])->toBe('Test Item')
        ->and($items['testItem']['priority'])->toBe(500)
        ->and($items['testItem']['permission'])->toBe(['Admin.TestItem']);
});

it('adds and gets visible navigation items correctly', function() {
    $this->actingAs(User::factory()->superUser()->create(), 'igniter-admin');
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
        'child' => [
            'testChildItem' => [
                'code' => 'testChildItem',
                'class' => 'testChildClass',
                'href' => 'http://localhost/admin/testChildItem',
                'icon' => 'fa fa-angle-double-left',
                'title' => 'Test Child Item',
                'priority' => 2000,
                'permission' => ['Admin.TestChildItem'],
            ],
            'testChildItem2' => [
                'code' => 'testChildItem2',
                'class' => 'testChildClass2',
                'href' => 'http://localhost/admin/testChildItem2',
                'icon' => 'fa fa-angle-double-right',
                'title' => 'Test Child Item 2',
                'priority' => 1000,
                'permission' => ['Admin.TestChildItem2'],
            ],
        ],
    ]);

    $items = $this->navigation->getVisibleNavItems();

    expect($items['testItem']['code'])->toBe('testItem')
        ->and($items['testItem']['class'])->toBe('testClass')
        ->and($items['testItem']['href'])->toBe('http://localhost/admin/testItem')
        ->and($items['testItem']['icon'])->toBe('fa fa-angle-double-right')
        ->and($items['testItem']['title'])->toBe('Test Item')
        ->and($items['testItem']['priority'])->toBe(500)
        ->and($items['testItem']['permission'])->toBe(['Admin.TestItem']);
});

it('removes navigation item correctly', function() {
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
    ]);

    $this->navigation->removeNavItem('testItem');

    $items = $this->navigation->getNavItems();

    expect($items)->not->toHaveKey('testItem');
});

it('removes navigation child item correctly', function() {
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
    ]);
    $this->navigation->addNavItem('testChildItem', [
        'code' => 'testChildItem',
        'class' => 'testChildClass',
        'href' => 'http://localhost/admin/testChildItem',
        'icon' => 'fa fa-angle-double-left',
        'title' => 'Test Child Item',
        'priority' => 2000,
        'permission' => ['Admin.TestChildItem'],
    ], 'testItem');

    $this->navigation->removeNavItem('testChildItem', 'testItem');

    $items = $this->navigation->getNavItems();

    expect($items['testItem']['child'])->not->toHaveKey('testItem');
});

it('removes main menu item correctly', function() {
    $this->navigation->registerMainItems(['testItem' => [
        [
            'code' => 'testItem',
            'class' => 'testClass',
            'href' => 'http://localhost/admin/testItem',
            'icon' => 'fa fa-angle-double-right',
            'title' => 'Test Item',
            'priority' => 500,
            'permission' => ['Admin.TestItem'],
        ],
    ]]);

    $this->navigation->removeMainItem('testItem');

    $items = $this->navigation->getMainItems();

    expect($items)->not->toHaveKey('testItem');
});

it('merges navigation items correctly', function() {
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
    ]);
    $this->navigation->addNavItem('testChildItem', [
        'code' => 'testChildItem',
        'class' => 'testChildClass',
        'href' => 'http://localhost/admin/testChildItem',
        'icon' => 'fa fa-angle-double-left',
        'title' => 'Test Child Item',
        'priority' => 2000,
        'permission' => ['Admin.TestChildItem'],
    ], 'testItem');

    $this->navigation->mergeNavItem('testItem', [
        'class' => 'newTestClass',
        'href' => 'http://localhost/admin/newTestItem',
        'icon' => 'fa fa-angle-double-left',
        'title' => 'New Test Item',
        'priority' => 1000,
        'permission' => ['Admin.NewTestItem'],
    ]);

    $this->navigation->mergeNavItem('testChildItem', [
        'class' => 'newTestChildClass',
        'title' => 'New Test Child Item',
    ], 'testItem');

    $items = $this->navigation->getNavItems();

    expect($items['testItem']['class'])->toBe('newTestClass')
        ->and($items['testItem']['href'])->toBe('http://localhost/admin/newTestItem')
        ->and($items['testItem']['icon'])->toBe('fa fa-angle-double-left')
        ->and($items['testItem']['title'])->toBe('New Test Item')
        ->and($items['testItem']['priority'])->toBe(1000)
        ->and($items['testItem']['permission'])->toBe(['Admin.NewTestItem'])
        ->and($items['testItem']['child']['testChildItem']['class'])->toBe('newTestChildClass')
        ->and($items['testItem']['child']['testChildItem']['title'])->toBe('New Test Child Item');
});

it('filters permitted navigation items correctly', function() {
    $this->navigation->addNavItem('testItem', [
        'code' => 'testItem',
        'class' => 'testClass',
        'href' => 'http://localhost/admin/testItem',
        'icon' => 'fa fa-angle-double-right',
        'title' => 'Test Item',
        'priority' => 500,
        'permission' => ['Admin.TestItem'],
    ]);

    $this->navigation->addNavItem('testItem2', [
        'code' => 'testItem2',
        'class' => 'testClass2',
        'href' => 'http://localhost/admin/testItem2',
        'icon' => 'fa fa-angle-double-left',
        'title' => 'Test Item 2',
        'priority' => 1000,
        'permission' => ['Admin.TestItem2'],
    ]);

    // Mock the AdminAuth facade to return a mock user object with the 'hasPermission' method
    $mockUser = Mockery::mock(User::class);
    $mockUser->shouldReceive('hasPermission')->andReturnUsing(function($permission) {
        return in_array('Admin.TestItem', $permission);
    });

    AdminAuth::shouldReceive('user')->andReturn($mockUser);

    $items = $this->navigation->getVisibleNavItems();

    expect($items)->toHaveKey('testItem')
        ->and($items)->not->toHaveKey('testItem2');
});

it('sets previous URL with full URL', function() {
    $navigation = new Navigation();
    $url = 'https://example.com/page';

    $navigation->setPreviousUrl($url);

    expect($navigation->getPreviousUrl())->toBe($url);
});

it('sets previous URL with query parameters', function() {
    request()->headers->set('referer', 'https://example.com/page?query=1');
    $navigation = new Navigation();

    $navigation->setPreviousUrl('https://example.com/page');

    expect($navigation->getPreviousUrl())->toBe('https://example.com/page?query=1');
});
