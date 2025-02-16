<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\ColorPicker;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->defaultValue = '#1abc9c';
    $this->controller = $this->createMock(AdminController::class);
    $this->formField = new FormField('test', 'Color picker');
    $this->colorPickerWidget = new ColorPicker($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function() {
    expect($this->colorPickerWidget->showAlpha)->toBeFalse();
    expect($this->colorPickerWidget->readOnly)->toBeFalse();
    expect($this->colorPickerWidget->disabled)->toBeFalse();
});

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
    expect($this->colorPickerWidget->render())->toBeString();
});

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
