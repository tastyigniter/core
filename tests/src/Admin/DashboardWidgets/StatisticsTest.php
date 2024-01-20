<?php

namespace Tests\Admin\DashboardWidgets;

use Igniter\Admin\DashboardWidgets\Statistics;
use Tests\Admin\Fixtures\Controllers\TestController;

it('renders widget with no errors', function () {
    $controller = resolve(TestController::class);
    $widget = new Statistics($controller, ['context' => 'sale']);

    $widget->render();

    expect($widget->vars['statsContext'])->toEqual('sale')
        ->and($widget->vars['statsLabel'])->toBe('lang:igniter::admin.dashboard.text_total_sale')
        ->and($widget->vars['statsIcon'])->toBe(' text-success fa fa-4x fa-line-chart')
        ->and($widget->vars['statsCount'])->toBe('£0.00');
});