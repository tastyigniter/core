<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\ListColumn;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Lists;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;

beforeEach(function () {
    $this->controller = new class extends AdminController
    {
        public function refreshList($alias)
        {
            return [$alias => 'refreshed'];
        }
    };
    $this->listsWidget = new Lists($this->controller, [
        'title' => 'Statuses',
        'model' => new Status,
        'toolbar' => [
            'buttons' => [
                'new' => [
                    'label' => 'New',
                    'class' => 'btn btn-primary',
                    'href' => 'admin/model/create',
                ],
            ],
        ],
        'bulkActions' => [
            'delete' => [
                'label' => 'Delete',
            ],
        ],
        'columns' => [
            'status_id' => [
                'label' => 'ID',
                'type' => 'number',
                'searchable' => true,
            ],
            'status_name' => [
                'label' => 'Name',
                'type' => 'text',
                'searchable' => true,
            ],
            'status_for' => [
                'label' => 'Status For',
                'type' => 'switch',
                'searchable' => true,
            ],
            'created_at' => [
                'label' => 'Comment',
                'type' => 'textarea',
                'invisible' => true,
            ],
        ],
    ]);
});

it('loads assets correctly', function () {
    Assets::shouldReceive('addJs')->once()->with('lists.js', 'lists-js');

    $this->listsWidget->assetPath = [];

    $this->listsWidget->loadAssets();
});

it('renders correctly', function () {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('lists/list'));

    expect($this->listsWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares var correctly', function () {
    $this->listsWidget->prepareVars();

    expect($this->listsWidget->vars)->toBeArray()
        ->toHaveKey('listId')
        ->toHaveKey('bulkActions')
        ->toHaveKey('columns')
        ->toHaveKey('columnTotal')
        ->toHaveKey('records')
        ->toHaveKey('emptyMessage')
        ->toHaveKey('showCheckboxes')
        ->toHaveKey('showDragHandle')
        ->toHaveKey('showSetup')
        ->toHaveKey('showFilter')
        ->toHaveKey('showPagination')
        ->toHaveKey('showPageNumbers')
        ->toHaveKey('showSorting')
        ->toHaveKey('sortColumn')
        ->toHaveKey('sortDirection');
});

it('gets columns', function () {
    expect($this->listsWidget->getColumns())->toBeArray();
});

it('gets column', function () {
    $this->listsWidget->prepareVars();

    $column = $this->listsWidget->getColumn('status_name');

    expect($column)->toBeInstanceOf(ListColumn::class);
});

it('gets visible column', function () {
    $visibleColumns = $this->listsWidget->getVisibleColumns();

    expect($visibleColumns)->toBeArray()->not->toHaveKey('created_at');
});

it('adds column', function () {
    $columns = ['testColumn' => ['label' => 'Test Column']];

    $this->listsWidget->addColumns($columns);

    expect($this->listsWidget->getColumns())->toHaveKey('testColumn');
});

it('removes column', function () {
    $columns = ['testColumn' => ['label' => 'Test Column']];

    $this->listsWidget->addColumns($columns);

    expect($this->listsWidget->getColumns())->toHaveKey('testColumn');

    $this->listsWidget->removeColumn('testColumn');

    expect($this->listsWidget->getColumns())->not->toHaveKey('testColumn');
});

it('gets button attributes', function () {
    $listColumn = new ListColumn('testColumn', 'Test Column');
    $listColumn->displayAs('text', ['attributes' => ['class' => 'btn btn-primary', 'href' => 'model/edit']]);

    $record = Status::factory()->create();

    $buttonAttributes = $this->listsWidget->getButtonAttributes($record, $listColumn);

    expect($buttonAttributes)->toBeString()
        ->toBe(' class="btn btn-primary" href="http://localhost/admin/model/edit"');
});

it('gets text column value', function ($columnName, $type, $value, $expected, $config) {
    $listColumn = new ListColumn($columnName, 'Test Column');
    $listColumn->displayAs($type, $config);

    $record = Status::factory()->create([
        $columnName => $value,
    ]);

    $columnValue = $this->listsWidget->getColumnValue($record, $listColumn);

    expect($columnValue)->toBe($expected);
})->with([
    ['status_name', 'text', 'Test Value', 'Test Value', []],
    ['status_name', 'partial', 'This is a test partial view', 'This is a test partial view', ['path' => 'tests.admin::_partials.test-partial']],
    ['status_name', 'money', 100.1234567, '100.12', []],
    ['status_name', 'switch', 1, 'Yes', ['onText' => 'Yes']],
    ['updated_at', 'datetime', '2022-12-31 23:59:59', '31 December 2022 23:59', ['format' => 'DD MMMM YYYY HH:mm']],
    ['updated_at', 'time', '23:59:59', '23:59', ['format' => 'HH:mm']],
    ['updated_at', 'date', '2022-12-31', '31 December 2022', ['format' => 'DD MMMM YYYY']],
    ['status_name', 'currency', 100, '£100.00', []],
]);

it('adds filter', function () {
    $calledFilter = false;

    $this->listsWidget->addFilter(function ($query) use (&$calledFilter) {
        $calledFilter = true;
    });

    $this->listsWidget->prepareVars();

    expect($calledFilter)->toBeTrue();
});

it('handles onSort action', function () {
    request()->query->add(['sort_by' => 'status_id']);

    $this->listsWidget->prepareVars();

    expect($this->listsWidget->vars['sortDirection'])->toBe('desc');

    $this->listsWidget->onSort();

    expect($this->listsWidget->vars['sortDirection'])->toBe('asc');

    $this->listsWidget->onSort();

    expect($this->listsWidget->vars['sortDirection'])->toBe('desc');
});

it('handles onLoadSetup action', function () {
    $loadSetupResult = $this->listsWidget->onLoadSetup();

    expect($loadSetupResult)
        ->toBeArray()
        ->toHaveKey('#'.$this->listsWidget->getId().'-setup-modal-content');
});

it('handles onApplySetup action', closure: function () {
    request()->request->add([
        'visible_columns' => $visibleColumns = ['status_id', 'status_name'],
        'page_limit' => $pageLimit = 20,
    ]);

    $this->listsWidget->prepareVars();

    expect($this->listsWidget->vars['columns'])->toBeArray()->toHaveKey('status_for');

    $this->listsWidget->onApplySetup();

    expect($this->listsWidget->vars['columns'])
        ->toBeArray()
        ->not->toHaveKey('status_for')
        ->and($this->listsWidget->getSession('visible'))->toBe($visibleColumns)
        ->and($this->listsWidget->getSession('order'))->toBe($visibleColumns)
        ->and($this->listsWidget->getSession('page_limit'))->toBe($pageLimit);
});

it('handles onResetSetup action', function () {
    $this->listsWidget->putSession('visible', ['status_id', 'status_name']);
    $this->listsWidget->putSession('order', ['status_id', 'status_name']);
    $this->listsWidget->putSession('page_limit', 20);

    $this->listsWidget->onResetSetup();

    expect($this->listsWidget->getSession('visible'))->toBeNull()
        ->and($this->listsWidget->getSession('order'))->toBeNull()
        ->and($this->listsWidget->getSession('page_limit'))->toBeNull();

});

it('handles onBulkAction action', function () {
    request()->query->add(['code' => 'delete']);
    request()->query->add(['checked' => [
        $status1 = Status::factory()->create()->getKey(),
        $status2 = Status::factory()->create()->getKey(),
        $status3 = Status::factory()->create()->getKey(),
    ]]);

    $this->listsWidget->prepareVars();

    $this->listsWidget->onBulkAction();

    $this->assertDatabaseMissing('statuses', ['status_id' => $status1])
        ->assertDatabaseMissing('statuses', ['status_id' => $status2])
        ->assertDatabaseMissing('statuses', ['status_id' => $status3]);
});
