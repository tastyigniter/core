<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\BulkActionWidgets;

use Igniter\Admin\BulkActionWidgets\Delete;
use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Tests\Fixtures\Controllers\TestController;

it('deletes records in bulk', function() {
    $actionButton = new ToolbarButton('delete');
    $actionButton->displayAs('link', []);

    $controller = resolve(TestController::class);
    $widget = new Delete($controller, $actionButton, []);
    $widget->code = $actionButton->name;

    StatusHistory::factory()->count(10)->create();

    expect(StatusHistory::count())->toBe(10);

    $widget->handleAction([], StatusHistory::get());

    expect(StatusHistory::count())->toBe(0);
});

it('does noting when records is empty', function() {
    $actionButton = new ToolbarButton('delete');
    $actionButton->displayAs('link', []);

    $controller = resolve(TestController::class);
    $widget = new Delete($controller, $actionButton, []);
    $widget->code = $actionButton->name;

    expect(StatusHistory::count())->toBe(0);

    $widget->handleAction([], StatusHistory::get());

    expect(StatusHistory::count())->toBe(0);
});
