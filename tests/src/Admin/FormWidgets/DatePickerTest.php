<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\DatePicker;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;
use Tests\Admin\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'Date picker');
    $this->datePickerWidget = new DatePicker($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function() {
    expect($this->datePickerWidget->mode)->toBe('date')
        ->and($this->datePickerWidget->startDate)->toBeNull()
        ->and($this->datePickerWidget->endDate)->toBeNull()
        ->and($this->datePickerWidget->dateFormat)->toBe('Y-m-d')
        ->and($this->datePickerWidget->timeFormat)->toBe('H:i')
        ->and($this->datePickerWidget->datesDisabled)->toBeArray();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addCss')->once()->with('datepicker.css', 'datepicker-css');

    $this->datePickerWidget->assetPath = [];

    $this->datePickerWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('datepicker/datepicker'));

    expect($this->datePickerWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares vars correctly', function() {
    $this->datePickerWidget->prepareVars();

    expect($this->datePickerWidget->vars)
        ->toHaveKey('name')
        ->toHaveKey('timeFormat')
        ->toHaveKey('dateFormat')
        ->toHaveKey('dateTimeFormat')
        ->toHaveKey('formatAlias')
        ->toHaveKey('value')
        ->toHaveKey('field')
        ->toHaveKey('mode')
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('datesDisabled');
});

it('gets save value correctly', function() {
    $value = '2022-12-31';
    $result = $this->datePickerWidget->getSaveValue($value);

    expect($result)->toBe($value);
});