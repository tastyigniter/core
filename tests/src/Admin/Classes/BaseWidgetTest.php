<?php

namespace Tests\Admin\Classes;

use Tests\Admin\Fixtures\Controllers\TestController;
use Tests\Admin\Fixtures\Widgets\TestWidget;

it('has defined paths to locate widget partials', function () {
    $controller = resolve(TestController::class);

    $widget = $controller->makeWidget(TestWidget::class);

    expect('tests.admin::_partials.fixtures/widgets/testwidget')
        ->toBeIn($widget->partialPath)
        ->and('tests.admin::_partials.fixtures/widgets')
        ->toBeIn($widget->partialPath);
});

it('has defined paths to locate widget asset files', function () {
    $controller = resolve(TestController::class);

    $widget = $controller->makeWidget(TestWidget::class);

    expect('igniter::css/fixtures/widgets')->toBeIn($widget->assetPath);
});
