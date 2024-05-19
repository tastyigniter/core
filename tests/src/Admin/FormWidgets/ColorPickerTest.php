<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\ColorPicker;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Models\TestModel;

dataset('initialization', [
    ['showAlpha', false],
    ['readOnly', false],
    ['disabled', false],
]);

beforeEach(function() {
    $this->defaultValue = '#1abc9c';
    $this->controller = $this->createMock(AdminController::class);
    $this->formField = new FormField('test', 'Color picker');
    $this->colorPickerWidget = new ColorPicker($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function($property, $expected) {
    expect($this->colorPickerWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('colorpicker.js', 'colorpicker-js');

    $this->colorPickerWidget->assetPath = [];

    $this->colorPickerWidget->loadAssets();
});

it('prepares variables correctly', function() {
    $this->formField->value = $this->defaultValue;

    $this->colorPickerWidget->prepareVars();

    expect($this->colorPickerWidget->vars)
        ->toHaveKey('name')
        ->toHaveKey('value')
        ->toHaveKey('availableColors')
        ->toHaveKey('showAlpha')
        ->toHaveKey('readOnly')
        ->toHaveKey('disabled');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->expects($this->atLeastOnce())
        ->method('exists')
        ->with($this->stringContains('colorpicker/colorpicker'));

    $this->colorPickerWidget->render();
})->throws(\Exception::class);

it('gets save value correctly', function() {
    $value = $this->colorPickerWidget->getSaveValue($this->defaultValue);

    expect($value)->toBe($this->defaultValue);
});

it('gets save value correctly when value is empty', function() {
    $value = $this->colorPickerWidget->getSaveValue('');

    expect($value)->toBeNull();
});

it('gets available colors correctly', function() {
    $this->colorPickerWidget->prepareVars();

    expect($this->colorPickerWidget->vars['availableColors'])->toBeArray()
        ->and($this->colorPickerWidget->vars['availableColors'])->toHaveCount(12);
});
