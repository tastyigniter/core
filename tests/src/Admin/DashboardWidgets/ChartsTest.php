<?php

namespace Tests\Admin\DashboardWidgets;

use Igniter\Admin\DashboardWidgets\Charts;
use Illuminate\Support\Facades\Event;
use Tests\Admin\Fixtures\Controllers\TestController;

it('fires admin.charts.extendDatasets event', function () {
    Event::fake();

    $controller = resolve(TestController::class);
    $widget = new Charts($controller, [
        'startDate' => now()->subDay(30),
        'endDate' => now()
    ]);

    $widget->render();

    Event::assertDispatched('admin.charts.extendDatasets');
});