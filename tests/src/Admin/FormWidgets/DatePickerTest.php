<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\DatePicker;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'Date picker');
    $this->datePickerWidget = new DatePicker($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function() {
    $this->datePickerWidget->initialize();

    expect($this->datePickerWidget->mode)->toBe('date')
        ->and($this->datePickerWidget->startDate)->toBeNull()
        ->and($this->datePickerWidget->endDate)->toBeNull()
        ->and($this->datePickerWidget->dateFormat)->toBe('Y-m-d')
        ->and($this->datePickerWidget->timeFormat)->toBe('H:i')
        ->and($this->datePickerWidget->datesDisabled)->toBeArray();
});

it('initializes with startDate and endDate string correctly', function() {
    $this->datePickerWidget->config['startDate'] = '2022-01-01';
    $this->datePickerWidget->config['endDate'] = '2022-12-31';

    $this->datePickerWidget->initialize();

    expect($this->datePickerWidget->mode)->toBe('date')
        ->and($this->datePickerWidget->startDate->toDateString())->toBe('2022-01-01')
        ->and($this->datePickerWidget->endDate->toDateString())->toBe('2022-12-31');
});

it('initializes with startDate and endDate timestamp correctly', function() {
    $this->datePickerWidget->config['startDate'] = strtotime('2022-01-01');
    $this->datePickerWidget->config['endDate'] = strtotime('2022-12-31');

    $this->datePickerWidget->initialize();

    expect($this->datePickerWidget->mode)->toBe('date')
        ->and($this->datePickerWidget->startDate->toDateString())->toBe('2022-01-01')
        ->and($this->datePickerWidget->endDate->toDateString())->toBe('2022-12-31');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addCss')->once()->with('datepicker.css', 'datepicker-css');

    $this->datePickerWidget->assetPath = [];

    $this->datePickerWidget->loadAssets();
});

it('renders correctly', function() {
    expect($this->datePickerWidget->render())->toBeString();
});

it('prepares vars in datetime mode correctly', function() {
    $this->formField->value = '2022-12-31 23:59:59';
    $this->datePickerWidget->mode = 'datetime';

    $this->datePickerWidget->prepareVars();

    expect($this->datePickerWidget->vars)
        ->toHaveKey('name', 'test_field')
        ->toHaveKey('timeFormat')
        ->toHaveKey('dateFormat')
        ->toHaveKey('dateTimeFormat', 'Y-m-d H:i')
        ->toHaveKey('datePickerFormat', 'DD MMM YYYY HH:mm')
        ->toHaveKey('formatAlias', lang('igniter::system.php.date_time_format'))
        ->toHaveKey('value')
        ->toHaveKey('field')
        ->toHaveKey('mode')
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('datesDisabled');
});

it('prepares vars in date mode correctly', function() {
    $this->formField->value = '2022-12-31';
    $this->datePickerWidget->mode = 'date';

    $this->datePickerWidget->prepareVars();

    expect($this->datePickerWidget->vars)
        ->toHaveKey('name', 'test_field')
        ->toHaveKey('timeFormat')
        ->toHaveKey('dateFormat', 'Y-m-d')
        ->toHaveKey('dateTimeFormat')
        ->toHaveKey('datePickerFormat', 'yyyy-mm-dd')
        ->toHaveKey('formatAlias', lang('igniter::system.php.date_format'))
        ->toHaveKey('value')
        ->toHaveKey('field')
        ->toHaveKey('mode')
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('datesDisabled');
});

it('prepares vars in time mode correctly', function() {
    $this->formField->value = '12:00:00';
    $this->datePickerWidget->mode = 'time';

    $this->datePickerWidget->prepareVars();

    expect($this->datePickerWidget->vars)
        ->toHaveKey('name', 'test_field')
        ->toHaveKey('timeFormat', 'H:i')
        ->toHaveKey('dateFormat')
        ->toHaveKey('dateTimeFormat', 'Y-m-d H:i')
        ->toHaveKey('formatAlias', lang('igniter::system.php.time_format'))
        ->toHaveKey('value')
        ->toHaveKey('field')
        ->toHaveKey('mode')
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('datesDisabled');
});

it('returns save value correctly', function() {
    $value = '2022-12-31';
    $result = $this->datePickerWidget->getSaveValue($value);

    expect($result)->toBe($value);
});

it('returns save value correctly when value is empty', function() {
    $value = '';
    $result = $this->datePickerWidget->getSaveValue($value);

    expect($result)->toBeNull();
});

it('returns save value correctly when field is disabled', function() {
    $this->formField->disabled = true;
    $value = '2022-12-31';
    $result = $this->datePickerWidget->getSaveValue($value);

    expect($result)->toBe(-1);
});
