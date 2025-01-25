<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\TableDataSource;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Table;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->tableWidget = new Table($this->controller, [
        'columns' => [
            'column1' => [
                'title' => 'Column 1',
                'type' => 'text',
            ],
            'status_name' => [
                'label' => 'Column 2',
                'type' => 'text',
                'partial' => 'test-partial',
            ],
        ],
    ]);
});

it('throws exception when intializing with invalid dataSource class', function() {
    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.error_table_widget_data_class_not_found'), 'InvalidDataSourceClass'));

    $tableWidget = new class($this->controller, [
        'columns' => [
            'column1' => [
                'title' => 'Column 1',
                'type' => 'text',
            ],
        ],
    ]) extends Table
    {
        protected string $dataSourceAliases = 'InvalidDataSourceClass';
    };


    $tableWidget->initialize();
});

it('initializes correctly with existing request data', function() {
    $expected = [
        'search' => 'search',
        'offset' => 'offset',
        'limit' => 'limit',
        'column1' => 'limit',
        'status_name' => 'limit',
    ];
    request()->setMethod('POST');
    request()->request->add([
        'tableTableData' => $expected,
    ]);

    $this->tableWidget->initialize();

    expect($this->tableWidget->getDataSource())->toBeInstanceOf(TableDataSource::class)
        ->and($this->tableWidget->getDataSource()->getRecords(0, 10))->toBe($expected);
});

it('initializes correctly with existing nested request data', function() {
    $expected = [
        'search' => 'search',
        'offset' => 'offset',
        'limit' => 'limit',
        'column1' => 'limit',
        'status_name' => 'limit',
    ];
    request()->setMethod('POST');
    request()->request->add([
        'customer' => [
            'table' => [
                'TableData' => $expected,
            ],
        ],
    ]);

    $this->tableWidget->alias = 'customer[table]';
    $this->tableWidget->initialize();

    expect($this->tableWidget->getDataSource())->toBeInstanceOf(TableDataSource::class)
        ->and($this->tableWidget->getDataSource()->getRecords(0, 10))->toBe($expected);
});

it('getDataSource method returns TableDataSource instance', function() {
    $dataSource = $this->tableWidget->getDataSource();

    expect($dataSource)->toBeInstanceOf(TableDataSource::class);
});

it('renders without errors', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));
    $viewMock->method('exists')->with($this->stringContains('table/table'));

    expect($this->tableWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares variables correctly', function() {
    $this->tableWidget->prepareVars();

    expect($this->tableWidget->vars)
        ->toBeArray()
        ->toHaveKey('tableId')
        ->toHaveKey('tableAlias')
        ->toHaveKey('columns')
        ->toHaveKey('recordsKeyFrom')
        ->toHaveKey('showPagination')
        ->toHaveKey('pageLimit')
        ->toHaveKey('toolbar')
        ->toHaveKey('height')
        ->toHaveKey('dynamicHeight')
        ->toHaveKey('useAjax')
        ->toHaveKey('clientDataSourceClass')
        ->toHaveKey('data');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addCss')->once()->with('table.css', 'table-css');
    Assets::shouldReceive('addJs')->once()->with('table.js', 'table-js');

    $this->tableWidget->assetPath = [];

    $this->tableWidget->loadAssets();
});

it('prepares columns array correctly', function() {
    expect($this->tableWidget->prepareColumnsArray())->toBeArray();
});

it('getAttributes method returns string', function() {
    expect($this->tableWidget->getAttributes())->toBeString();
});

it('handles onGetRecords action correctly', function() {
    request()->request->add([
        'search' => 'search',
        'offset' => 'offset',
        'limit' => 'limit',
    ]);

    $this->tableWidget->bindEvent('table.getRecords', function() {
        return Status::paginate(10);
    });

    expect($this->tableWidget->onGetRecords())
        ->toBeArray()
        ->toHaveKey('rows')
        ->toHaveKey('total');
});

it('handles onGetDropdownOptions action correctly', function() {
    request()->request->add([
        'column' => 'column1',
        'rowData' => [],
    ]);

    $this->tableWidget->bindEvent('table.getDropdownOptions', function() {
        return Status::getDropdownOptionsForOrder();
    });

    $this->tableWidget->bindEvent('table.getDropdownOptions', function() {
        return Status::getDropdownOptionsForReservation();
    });

    $options = $this->tableWidget->onGetDropdownOptions();
    expect($options)->toBeArray()->toHaveKey('options')
        ->and($options['options'])->not->toBeEmpty();
});
