<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Exception;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\DataTable;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Widgets\Table;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Connector');
    $this->dataTableWidget = new DataTable($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'searchableFields' => ['comment'],
        'showRefreshButton' => true,
    ]);
});

it('initializes correctly', function() {
    $this->dataTableWidget->useAjax = true;

    $this->dataTableWidget->initialize();

    expect($this->dataTableWidget->size)->toBe('large')
        ->and($this->dataTableWidget->defaultSort)->toBeNull()
        ->and($this->dataTableWidget->searchableFields)->toBeArray()
        ->and($this->dataTableWidget->showRefreshButton)->toBeTrue()
        ->and($this->dataTableWidget->useAjax)->toBeTrue()
        ->and($this->dataTableWidget->config['attributes'])->toHaveKeys([
            'data-search', 'data-show-refresh', 'data-side-pagination', 'data-silent-sort',
        ]);
});

it('prepares vars correctly', function() {
    $this->dataTableWidget->prepareVars();

    expect($this->dataTableWidget->vars)
        ->toHaveKey('table')
        ->toHaveKey('dataTableId')
        ->toHaveKey('size');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('datatable/datatable'));

    $this->dataTableWidget->render();
})->throws(Exception::class);

it('returns load value correctly when value is a collection', function() {
    expect($this->dataTableWidget->getLoadValue())->toBeArray();
});

it('returns load value correctly when value is an array', function() {
    $this->formField->value = [
        ['name' => 'Test'],
    ];

    $value = $this->dataTableWidget->getLoadValue();

    expect($value[0])->toHaveKey('id');
});

it('returns save value', function() {
    StatusHistory::factory()->for($this->dataTableWidget->model, 'status')->count(2)->create();

    $this->dataTableWidget->prepareVars();
    $result = $this->dataTableWidget->getSaveValue([]);

    expect($result)->toBeArray();
});

it('gets table correctly', function() {
    expect($this->dataTableWidget->getTable())->toBeInstanceOf(Table::class);
});

it('gets data table records correctly', function() {
    $this->dataTableWidget->defaultSort = ['status_id', 'desc'];

    expect($this->dataTableWidget->getDataTableRecords(0, 10, 'comment'))
        ->toBeInstanceOf(LengthAwarePaginator::class);
});

it('returns options from specific method if exists', function() {
    $model = mock(Model::class);
    $model->shouldReceive('methodExists')->with('getStatusHistoryDataTableOptions')->andReturn(true);
    $model->shouldReceive('getStatusHistoryDataTableOptions')->with('field', [])->andReturn(['option1', 'option2']);
    $this->dataTableWidget->model = $model;

    $result = $this->dataTableWidget->getDataTableOptions('field', []);

    expect($result)->toBe(['option1', 'option2']);
});

it('returns options from generic method if specific method does not exist', function() {
    $model = mock(Model::class);
    $model->shouldReceive('methodExists')->with('getStatusHistoryDataTableOptions')->andReturn(false);
    $model->shouldReceive('methodExists')->with('getDataTableOptions')->andReturn(true);
    $model->shouldReceive('getDataTableOptions')->with('status_history', 'field', [])->andReturn(['option1', 'option2']);
    $this->dataTableWidget->model = $model;

    $result = $this->dataTableWidget->getDataTableOptions('field', []);

    expect($result)->toBe(['option1', 'option2']);
});

it('throws exception if neither specific nor generic method exists', function() {
    $model = mock(Model::class);
    $model->shouldReceive('methodExists')->with('getStatusHistoryDataTableOptions')->andReturn(false);
    $model->shouldReceive('methodExists')->with('getDataTableOptions')->andReturn(false);
    $this->dataTableWidget->model = $model;

    expect(fn() => $this->dataTableWidget->getDataTableOptions('field', []))->toThrow(SystemException::class);
});

it('returns empty array if method returns non-array value', function() {
    $model = mock(Model::class);
    $model->shouldReceive('methodExists')->with('getStatusHistoryDataTableOptions')->andReturn(true);
    $model->shouldReceive('getStatusHistoryDataTableOptions')->with('field', [])->andReturn('non-array value');
    $this->dataTableWidget->model = $model;

    $result = $this->dataTableWidget->getDataTableOptions('field', []);

    expect($result)->toBe([]);
});
