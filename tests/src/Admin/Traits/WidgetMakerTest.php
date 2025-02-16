<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Traits\WidgetMaker;
use Igniter\Flame\Exception\SystemException;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Widgets\TestFormWidget;
use Igniter\Tests\Fixtures\Widgets\TestWidget;

beforeEach(function() {
    $this->traitObject = new class
    {
        use WidgetMaker;

        public $controller;

        public $configKey;

        public $vars = [];
    };
    $this->traitObject->controller = resolve(TestController::class);
});

it('creates a widget instance with valid class and config', function() {
    $widget = $this->traitObject->makeWidget(TestWidget::class, ['property' => 'configValue']);

    expect($widget)->toBeInstanceOf(TestWidget::class)
        ->and($widget->property)->toBe('configValue');
});

it('throws exception when widget class does not exist', function() {
    expect(fn() => $this->traitObject->makeWidget('NonExistentClass'))->toThrow(SystemException::class);
});

it('creates a form widget instance with valid class and string field config', function() {
    $widget = $this->traitObject->makeFormWidget(TestFormWidget::class, 'fieldName', ['property' => 'configValue']);

    expect($widget)->toBeInstanceOf(TestFormWidget::class)
        ->and($widget->getFormField()->fieldName)->toBe('fieldName')
        ->and($widget->property)->toBe('configValue');
});

it('creates a form widget instance with valid class and array field config', function() {
    $fieldConfig = ['name' => 'fieldName', 'label' => 'Field Label'];
    $widget = $this->traitObject->makeFormWidget(TestFormWidget::class, $fieldConfig, ['property' => 'configValue']);

    $formField = $widget->getFormField();
    expect($widget)->toBeInstanceOf(TestFormWidget::class)
        ->and($formField->fieldName)->toBe('fieldName')
        ->and($formField->label)->toBe('Field Label')
        ->and($widget->property)->toBe('configValue');
});

it('creates a form widget instance with valid class and FormField object', function() {
    $formField = new FormField('fieldName', 'Field Label');
    $widget = $this->traitObject->makeFormWidget(TestFormWidget::class, $formField, ['property' => 'configValue']);

    expect($widget)->toBeInstanceOf(TestFormWidget::class)
        ->and($widget->getFormField())->toBe($formField)
        ->and($widget->property)->toBe('configValue');
});

it('throws exception when form widget class does not exist', function() {
    expect(fn() => $this->traitObject->makeFormWidget('NonExistentClass', 'fieldName'))->toThrow(SystemException::class);
});
