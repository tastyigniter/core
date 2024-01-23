<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Connector;
use Igniter\Admin\Models\Status;
use Tests\Admin\Fixtures\Controllers\TestController;

it('renders form widget without errors', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('status_history', 'Connector');
    $formField->displayAs('connector');

    $widget = new Connector($controller, $formField, [
        'model' => Status::factory()->create(),
    ]);

    $widget->render();

    expect($widget->vars)->toHaveKeys([
        'fieldItems',
        'newRecordTitle',
    ]);
});

it('loads a record', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('status_history', 'Connector');
    $formField->displayAs('connector');

    $widget = new Connector($controller, $formField, [
        'model' => Status::factory()->create(),
    ]);

    expect($widget->onLoadRecord())->toBeString();
});

it('creates a record', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('status_history', 'Connector');
    $formField->displayAs('connector');

    $widget = new Connector($controller, $formField, [
        'model' => Status::factory()->create(),
    ]);

    expect($widget->onSaveRecord())->toBeArray();
})->skip();

it('deletes a record', function () {
    $controller = resolve(TestController::class);
    $formField = new FormField('status_history', 'Connector');
    $formField->displayAs('connector');
    request()->request->set('recordId', 1);

    $widget = new Connector($controller, $formField, [
        'model' => Status::factory()->create(),
    ]);

    expect($widget->onSaveRecord())->toBeArray();
})->skip();