<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\CodeEditor;
use Igniter\Admin\Models\Status;
use Tests\Admin\Fixtures\Controllers\TestController;

it('renders form widget without errors', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('test', 'Code editor');
    $formField->displayAs('codeeditor', [
        'size' => 'medium',
    ]);

    $widget = new CodeEditor($controller, $formField, [
        'model' => Status::factory()->create(),
        'mode' => 'php',
    ]);

    $widget->render();

    expect($widget->vars['size'])->toBe('medium')
        ->and($widget->vars['mode'])->toBe('php');
});