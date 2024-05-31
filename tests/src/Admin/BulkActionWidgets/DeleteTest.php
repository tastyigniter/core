<?php

namespace Igniter\Tests\Admin\BulkActionWidgets;

use Igniter\Admin\BulkActionWidgets\Delete;
use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Admin\Models\StatusHistory;
use Tests\Admin\Fixtures\Controllers\TestController;

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
