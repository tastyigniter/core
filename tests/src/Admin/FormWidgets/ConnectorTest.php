<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Connector;
use Tests\Admin\Fixtures\Controllers\TestController;

it('renders form widget without errors', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('test', 'Connector');
    $formField->displayAs('connector', [
        'size' => 'medium',
    ]);

    $widget = new Connector($controller, $formField, [
//        'model' => Status::factory()->create(),
//        'mode' => 'php',
    ]);

    $widget->render();

    expect($widget->vars['size'])->toBe('medium')
        ->and($widget->vars['mode'])->toBe('php');
});