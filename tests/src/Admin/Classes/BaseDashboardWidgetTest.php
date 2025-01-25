<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Tests\Fixtures\Controllers\TestController;

it('returns properties excluding startDate and endDate', function() {
    $controller = new TestController;
    $widget = new BaseDashboardWidget($controller, [
        'startDate' => '2023-01-01',
        'endDate' => '2023-12-31',
        'otherProperty' => 'value',
    ]);

    $result = $widget->getPropertiesToSave();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('otherProperty')
        ->and($result)->not->toHaveKey('startDate')
        ->and($result)->not->toHaveKey('endDate');
});

it('returns validation rules and attributes', function() {
    $controller = new TestController;
    $widget = new class($controller, [
        'startDate' => '2023-01-01',
        'endDate' => '2023-12-31',
        'otherProperty' => 'value',
    ]) extends BaseDashboardWidget
    {
        public function defineProperties(): array
        {
            return [
                'property1' => ['validationRule' => 'required', 'label' => 'Property 1'],
                'property2' => ['validationRule' => 'numeric', 'label' => 'Property 2'],
            ];
        }
    };

    [$rules, $attributes] = $widget->getPropertyRules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('property1')
        ->and($rules['property1'])->toBe('required')
        ->and($rules)->toHaveKey('property2')
        ->and($rules['property2'])->toBe('numeric')
        ->and($attributes)->toBeArray()
        ->and($attributes)->toHaveKey('property1')
        ->and($attributes['property1'])->toBe('Property 1')
        ->and($attributes)->toHaveKey('property2')
        ->and($attributes['property2'])->toBe('Property 2');
});

it('returns empty rules and attributes if no properties defined', function() {
    $controller = new TestController;
    $widget = new class($controller, []) extends BaseDashboardWidget
    {
        public function defineProperties(): array
        {
            return [];
        }
    };

    [$rules, $attributes] = $widget->getPropertyRules();

    expect($rules)->toBeArray()
        ->and($rules)->toBeEmpty()
        ->and($attributes)->toBeArray()
        ->and($attributes)->toBeEmpty();
});

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
