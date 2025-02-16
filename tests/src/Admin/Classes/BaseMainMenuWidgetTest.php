<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseMainMenuWidget;
use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Tests\Fixtures\Controllers\TestController;

it('constructs correctly', function() {
    $widget = new BaseMainMenuWidget(new TestController, new MainMenuItem('test-menu-item'), []);

    expect($widget->config)->toBeArray();
});

it('returns unique id with suffix', function() {
    $widget = new BaseMainMenuWidget(new TestController, new MainMenuItem('test-menu-item'), []);

    $id = $widget->getId('suffix');

    expect($id)->toBe('basemainmenuwidget-suffix-menuitem-test-menu-item');
});

it('returns unique id without suffix', function() {
    $widget = new BaseMainMenuWidget(new TestController, new MainMenuItem('test-menu-item'), []);

    $id = $widget->getId();

    expect($id)->toBe('basemainmenuwidget-menuitem-test-menu-item');
});
