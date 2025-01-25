<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Tests\Fixtures\Models\TestModel;

it('constructs correctly', function() {
    $formField = new FormField('testField', 'Test Field');
    $widget = new BaseFormWidget(new AdminController, $formField, []);

    expect($widget->model)->toBeNull()
        ->and($widget->data)->toBeNull()
        ->and($widget->sessionKey)->toBeNull()
        ->and($widget->previewMode)->toBeFalse()
        ->and($widget->showLabels)->toBeTrue()
        ->and($widget->config)->toBeArray();
});

it('returns unique id with suffix', function() {
    $formField = new FormField('testField', 'Test Field');
    $formField->fieldName = 'testField';
    $widget = new BaseFormWidget(new AdminController, $formField, []);

    $id = $widget->getId('suffix');

    expect($id)->toBe('baseformwidget-suffix-testField');
});

it('returns unique id without suffix', function() {
    $formField = new FormField('testField', 'Test Field');
    $widget = new BaseFormWidget(new AdminController, $formField, []);

    $id = $widget->getId();

    expect($id)->toBe('baseformwidget-testField');
});

it('can get save value', function() {
    $formField = new FormField('testField', 'Test Field');
    $widget = new BaseFormWidget(new AdminController, $formField, [
        'alias' => 'test-alias',
        'model' => new TestModel,
    ]);

    expect($widget->getSaveValue('test-value'))->toBe('test-value');
});

it('can get load value', function() {
    $formField = new FormField('testField', 'Test Field');
    $formField->displayAs('text');
    $widget = new BaseFormWidget(new AdminController, $formField, [
        'alias' => 'test-alias',
        'model' => new TestModel,
        'data' => ['testField' => 'test-value'],
    ]);

    expect($widget->getLoadValue())->toBe('test-value');
});
