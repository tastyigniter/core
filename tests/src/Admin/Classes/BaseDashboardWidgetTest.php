<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Tests\Fixtures\Controllers\TestController;

it('can get width', function() {
    $widget = new BaseDashboardWidget(new TestController, ['width' => 300]);
    expect($widget->getWidth())->toBe(300);
});

it('can get css class', function() {
    $widget = new BaseDashboardWidget(new TestController, ['cssClass' => 'test-class']);
    expect($widget->getCssClass())->toBe('test-class');
});

it('can get priority', function() {
    $widget = new BaseDashboardWidget(new TestController, ['priority' => 1]);
    expect($widget->getPriority())->toBe(1);
});

it('can get start date', function() {
    $date = now();
    $widget = new BaseDashboardWidget(new TestController, ['startDate' => $date]);
    expect($widget->getStartDate())->toBe($date);
});

it('can get end date', function() {
    $date = now();
    $widget = new BaseDashboardWidget(new TestController, ['endDate' => $date]);
    expect($widget->getEndDate())->toBe($date);
});
