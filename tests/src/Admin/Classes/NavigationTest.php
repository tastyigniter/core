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

it('registers a navigation item', function() {
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

it('removes navigation items correctly', function() {
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

    $this->navigation->mergeNavItem('testItem', [
        'class' => 'newTestClass',
        'href' => 'http://localhost/admin/newTestItem',
        'icon' => 'fa fa-angle-double-left',
        'title' => 'New Test Item',
        'priority' => 1000,
        'permission' => ['Admin.NewTestItem'],
    ]);

    $items = $this->navigation->getNavItems();

    expect($items['testItem']['class'])->toBe('newTestClass')
        ->and($items['testItem']['href'])->toBe('http://localhost/admin/newTestItem')
        ->and($items['testItem']['icon'])->toBe('fa fa-angle-double-left')
        ->and($items['testItem']['title'])->toBe('New Test Item')
        ->and($items['testItem']['priority'])->toBe(1000)
        ->and($items['testItem']['permission'])->toBe(['Admin.NewTestItem']);
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
