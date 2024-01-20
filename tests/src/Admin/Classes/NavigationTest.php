<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Facades\AdminMenu;

it('registers a navigation item', function () {
    AdminMenu::registerNavItems([
        'test' => [
            'code' => 'test',
            'title' => 'Test',
            'class' => 'test',
            'icon' => 'fa fa-angle-double-right',
            'href' => 'http://localhost/admin/test',
            'priority' => 0,
            'permission' => ['Admin.Test'],
        ]
    ]);

    $items = AdminMenu::getNavItems();

    expect($items['test']['code'])->toBe('test')
        ->and($items['test']['class'])->toBe('test')
        ->and($items['test']['href'])->toBe('http://localhost/admin/test')
        ->and($items['test']['icon'])->toBe('fa fa-angle-double-right')
        ->and($items['test']['title'])->toBe('Test')
        ->and($items['test']['priority'])->toBe(0)
        ->and($items['test']['permission'])->toBe(['Admin.Test'])
        ->and($items['test']['child'])->toBeNull();
});

it('loads registered admin navigation items', function () {
    $items = AdminMenu::getNavItems();

    expect($items)
        ->toHaveKeys([
            'dashboard',
            'restaurant',
            'sales.child.statuses',
            'sales',
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

it('loads registered admin main menu items', function () {
    $items = AdminMenu::getMainItems();

    expect($items)
        ->toHaveKeys(['preview', 'help', 'settings'])
        ->and($items['settings'])->toHaveProperties([
            'itemName', 'type', 'disabled', 'context', 'icon', 'attributes', 'priority', 'permission',
        ]);
});
