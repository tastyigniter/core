<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseBulkActionWidget;
use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Support\Collection;

it('constructs correctly', function() {
    $controller = new TestController;
    $actionButton = new ToolbarButton('test-toolbar-button');
    $config = [];

    $widget = new BaseBulkActionWidget($controller, $actionButton, $config);

    expect($widget)->toBeInstanceOf(BaseBulkActionWidget::class);
});

it('returns the action button', function() {
    $controller = new TestController;
    $actionButton = new ToolbarButton('test-toolbar-button');
    $config = [];

    $widget = new BaseBulkActionWidget($controller, $actionButton, $config);

    $returnedActionButton = $widget->getActionButton();

    expect($returnedActionButton)->toBeInstanceOf(ToolbarButton::class);
});

it('handles action correctly', function() {
    $controller = new TestController;
    $actionButton = new ToolbarButton('test-toolbar-button');
    $config = [];
    $requestData = [];
    $records = new Collection;

    $widget = new BaseBulkActionWidget($controller, $actionButton, $config);

    $widget->handleAction($requestData, $records);
})->throwsNoExceptions();
