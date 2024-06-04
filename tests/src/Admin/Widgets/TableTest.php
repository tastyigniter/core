<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\TableDataSource;
use Igniter\Admin\Widgets\Table;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->tableWidget = new Table($this->controller, [
        'dataSource' => 'TestDataSource',
        'columns' => [
            'column1' => [
                'label' => 'Column 1',
                'type' => 'text',
            ],
            'column2' => [
                'label' => 'Column 2',
                'type' => 'text',
            ],
        ],
    ]);
});

it('initialize method throws exception when dataSource is not specified', function() {
    $this->tableWidget->setConfig(['dataSource' => null]);

    $this->expectException(SystemException::class);

    $this->tableWidget->initialize();
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
        return new LengthAwarePaginator([], 0, 10, 1);
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

    $eventFired = false;

    $this->tableWidget->bindEvent('table.getDropdownOptions', function() use (&$eventFired) {
        $eventFired = true;
    });

    expect($this->tableWidget->onGetDropdownOptions())
        ->toBeArray()->toHaveKey('options')
        ->and($eventFired)->toBeTrue();
});
