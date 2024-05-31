<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\DataTable;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Factory;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Connector');
    $this->colorPickerWidget = new DataTable($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
    ]);
});

it('initializes correctly', function() {
    expect($this->colorPickerWidget->size)->toBe('large')
        ->and($this->colorPickerWidget->defaultSort)->toBeNull()
        ->and($this->colorPickerWidget->searchableFields)->toBeArray()
        ->and($this->colorPickerWidget->showRefreshButton)->toBeFalse()
        ->and($this->colorPickerWidget->useAjax)->toBeFalse();
});

it('prepares vars correctly', function() {
    $this->colorPickerWidget->prepareVars();

    expect($this->colorPickerWidget->vars)
        ->toHaveKey('table')
        ->toHaveKey('dataTableId')
        ->toHaveKey('size');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('datatable/datatable'));

    $this->colorPickerWidget->render();
})->throws(\Exception::class);

it('gets load value correctly', function() {
    expect($this->colorPickerWidget->getLoadValue())->toBeArray();
});

it('gets table correctly', function() {
    expect($this->colorPickerWidget->getTable())->toBeInstanceOf(Table::class);
});

it('gets data table records correctly', function() {
    expect($this->colorPickerWidget->getDataTableRecords(0, 10, ''))
        ->toBeInstanceOf(LengthAwarePaginator::class);
});
