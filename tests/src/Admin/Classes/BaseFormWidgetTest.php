<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Tests\Fixtures\Models\TestModel;

it('can get save value', function() {
    $formField = new FormField('testField', 'Test Field');
    $formField->displayAs('text');
    $widget = new BaseFormWidget(new AdminController(), $formField, [
        'alias' => 'test-alias',
        'model' => new TestModel,
    ]);

    expect($widget->getSaveValue('test-value'))->toBe('test-value');
});

it('can get load value', function() {
    $formField = new FormField('testField', 'Test Field');
    $formField->displayAs('text');
    $widget = new BaseFormWidget(new AdminController(), $formField, [
        'alias' => 'test-alias',
        'model' => new TestModel,
        'data' => ['testField' => 'test-value'],
    ]);

    expect($widget->getLoadValue())->toBe('test-value');
});
