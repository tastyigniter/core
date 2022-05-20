<?php

namespace Tests\Admin\Classes;

use Tests\Fixtures\Controllers\TestController;
use Tests\Fixtures\Widgets\TestWidget;

it('should have defined paths', function () {
    $controller = resolve(TestController::class);

    $widget = $controller->makeWidget(TestWidget::class);

    expect('tests.fixtures::_partials.widgets/testwidget')->toBeIn($widget->partialPath);
    expect('tests.fixtures::_partials.widgets')->toBeIn($widget->partialPath);

    expect('@/models/admin')->toBeIn($widget->configPath);
    expect('@/models/main')->toBeIn($widget->configPath);
    expect('@/models/system')->toBeIn($widget->configPath);

    expect('@/js/widgets')->toBeIn($widget->assetPath);
    expect('@/css/widgets')->toBeIn($widget->assetPath);
});
