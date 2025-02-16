<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\BulkActionWidgets;

use Igniter\Admin\BulkActionWidgets\Status;
use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Tests\Fixtures\Controllers\TestController;

it('updates record status column in bulk', function() {
    $statusColumn = 'notify';

    $actionButton = new ToolbarButton('status');
    $actionButton->displayAs('link', []);

    $controller = resolve(TestController::class);
    $widget = new Status($controller, $actionButton, ['statusColumn' => $statusColumn]);
    $widget->code = $actionButton->name;

    $records = StatusHistory::factory()->count(10)->create([
        $statusColumn => false,
    ]);

    expect(StatusHistory::where($statusColumn, false)->count())->toBe(10);

    $widget->handleAction(['code' => 'action.enable'], $records);

    expect(StatusHistory::where($statusColumn, true)->count())->toBe(10);
});

it('does noting when records is empty', function() {
    $statusColumn = 'notify';

    $actionButton = new ToolbarButton('status');
    $actionButton->displayAs('link', []);

    $controller = resolve(TestController::class);
    $widget = new Status($controller, $actionButton, ['statusColumn' => $statusColumn]);
    $widget->code = $actionButton->name;

    expect(StatusHistory::count())->toBe(0);

    $widget->handleAction(['code' => 'action.disable'], StatusHistory::get());

    expect(StatusHistory::count())->toBe(0);
});
