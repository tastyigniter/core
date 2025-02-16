<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\ListColumn;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Widgets\Lists;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Facades\Location as LocationFacade;
use Igniter\Local\Models\Location;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\Currency;
use Igniter\User\Models\User;
use InvalidArgumentException;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
        public function refreshList($alias = null): array
        {
            return [$alias => 'refreshed'];
        }
    };
    $this->widgetConfig = [
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
            'status' => [
                'label' => 'Status',
                'type' => 'dropdown',
                'menuItems' => [
                    'active' => [
                        'label' => 'Active',
                        'icon' => 'fa fa-check',
                    ],
                    'inactive' => [
                        'label' => 'Inactive',
                        'icon' => 'fa fa-times',
                    ],
                ],
            ],
        ],
        'columns' => [
            'status_id' => [
                'label' => 'ID',
                'type' => 'number',
                'searchable' => true,
            ],
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'select' => 'status_name',
                'searchable' => true,
            ],
            'status_for' => [
                'label' => 'Status For',
                'type' => 'switch',
                'searchable' => true,
            ],
            'created_at' => [
                'label' => 'Created At',
                'type' => 'datetime',
                'invisible' => true,
            ],
        ],
    ];
    $this->listsWidget = new Lists($this->controller, $this->widgetConfig);
    $this->listsWidget->bindToController();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('lists.js', 'lists-js');

    $this->listsWidget->assetPath = [];

    $this->listsWidget->loadAssets();
});

it('renders correctly', function() {
    $this->listsWidget->columns = array_map(function($column) {
        $column['sortable'] = false;

        return $column;
    }, $this->listsWidget->columns);

    expect($this->listsWidget->render())->toBeString();
});

it('prepares var correctly', function() {
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
        ->toHaveKey('showPagination')
        ->toHaveKey('showPageNumbers')
        ->toHaveKey('showSorting')
        ->toHaveKey('sortColumn')
        ->toHaveKey('sortDirection');
});

it('refreshes list widget on paginate', function() {
    $this->listsWidget->putSession('sort', ['status_id', 'desc']);
    expect($this->listsWidget->onPaginate())->toBeArray();
});

it('throws exception when initializing with missing model', function() {
    $this->widgetConfig['model'] = null;

    expect(fn() => new Lists($this->controller, $this->widgetConfig))
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.list.missing_model'), $this->controller::class));
});

it('applies search term to list results', function() {
    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->bindToController();
    $listsWidget->setSearchTerm('Test');

    $listsWidget->prepareVars();

    expect($listsWidget->vars['records'])->toHaveCount(0);
});

it('applies search term on related column', function() {
    $this->widgetConfig['showDragHandle'] = true;
    $this->widgetConfig['model'] = new Currency;
    $this->widgetConfig['columns'] = [
        'related' => [
            'label' => 'Related',
            'type' => 'text',
            'relation' => 'country',
            'select' => 'country_name',
            'searchable' => true,
        ],
        'country_id' => [
            'label' => 'Related',
            'type' => 'text',
            'relation' => 'country',
            'searchable' => true,
        ],
    ];
    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->setSearchOptions(['scope' => 'applySwitchable']);
    $listsWidget->setSearchTerm('Test');

    $listsWidget->prepareVars();

    expect($listsWidget->vars['records']->isNotEmpty())->toBeTrue();
});

it('throws an exception when applying custom select query on morphTo relation', function() {
    $this->widgetConfig['model'] = new StatusHistory;
    $this->widgetConfig['columns'] = [
        'order_id' => [
            'label' => 'Related',
            'type' => 'text',
            'relation' => 'object',
            'select' => 'name',
            'searchable' => true,
        ],
    ];
    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->setSearchTerm('Test');

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.list.alert_relationship_not_supported'), 'morphTo'));

    $listsWidget->prepareVars();
});

it('extends list query using events', function() {
    $this->widgetConfig['model'] = new Status;
    $this->widgetConfig['columns'] = [
        'status_name' => [
            'label' => 'Related',
            'type' => 'text',
            'searchable' => true,
        ],
    ];
    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->bindEvent('list.extendQuery', function($query) {
        return $query->where('status_id', 1);
    });

    $listsWidget->prepareVars();

    expect($listsWidget->vars['records'])->toHaveCount(1);
});

it('extends list records using events', function() {
    $this->listsWidget->showPagination = false;
    $this->listsWidget->bindEvent('list.extendRecords', function($records) {
        return $records;
    });

    expect($this->listsWidget->render())->toBeString();
});

it('returns all defined columns', function() {
    expect($this->listsWidget->getColumns())->toBeArray();
});

it('throws exception when missing defined columns', function() {
    $this->widgetConfig['columns'] = [];

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.list.missing_column'), $this->controller::class));

    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->prepareVars();
});

it('filter list columns using model filterColumns method', function() {
    $this->widgetConfig['model'] = new class extends Status
    {
        public function filterColumns(&$listColumn): array
        {
            unset($listColumn['status_id']);

            return $listColumn;
        }
    };
    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->prepareVars();

    expect($listsWidget->vars['columns'])->toHaveCount(2);
});

it('makes list column with custom name', function() {
    $listColumn = $this->listsWidget->makeListColumn('pivot[testColumn]', 'Test Column');

    expect($listColumn)->toBeInstanceOf(ListColumn::class)
        ->and($listColumn->valueFrom)->toBe('testColumn')
        ->and($listColumn->relation)->toBe('pivot');
});

it('returns column', function() {
    $this->listsWidget->prepareVars();

    $column = $this->listsWidget->getColumn('status_id');

    expect($column)->toBeInstanceOf(ListColumn::class);
});

it('returns visible column', function() {
    LocationFacade::setModel(Location::factory()->create());
    $this->listsWidget->columns['notify_customer'] = 'Notify Customer';
    $this->listsWidget->columns['status_color'] = [
        'label' => 'Status Color',
        'type' => 'text',
        'permissions' => ['Admin.Statuses'],
    ];
    $this->listsWidget->columns['status_comment'] = [
        'label' => 'Status Color',
        'type' => 'text',
        'locationAware' => true,
    ];
    $visibleColumns = $this->listsWidget->getVisibleColumns();

    expect($visibleColumns)->toBeArray()
        ->not->toHaveKeys(['created_at', 'status_color', 'status_comment']);
});

it('adds column', function() {
    $columns = ['testColumn' => ['label' => 'Test Column']];

    $this->listsWidget->addColumns($columns);

    expect($this->listsWidget->getColumns())->toHaveKey('testColumn');
});

it('removes column', function() {
    $columns = ['testColumn' => ['label' => 'Test Column']];

    $this->listsWidget->addColumns($columns);

    expect($this->listsWidget->getColumns())->toHaveKey('testColumn');

    $this->listsWidget->removeColumn('testColumn');

    expect($this->listsWidget->getColumns())->not->toHaveKey('testColumn');
});

it('returns button attributes', function() {
    $listColumn = new ListColumn('testColumn', 'Test Column');
    $listColumn->displayAs('text', ['attributes' => ['class' => 'btn btn-primary', 'href' => 'model/edit']]);

    $record = Status::factory()->create();

    $buttonAttributes = $this->listsWidget->getButtonAttributes($record, $listColumn);

    expect($buttonAttributes)->toBeString()
        ->toBe(' class="btn btn-primary" href="http://localhost/admin/model/edit"');
});

it('overrides list header value using event', function() {
    $this->listsWidget->bindEvent('list.overrideHeaderValue', function($column, $value): string {
        return 'Overridden Value';
    });

    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->displayAs('text', []);

    $columnValue = $this->listsWidget->getHeaderValue($listColumn);

    expect($columnValue)->toBe('Overridden Value');
});

it('returns list column value', function($columnName, $type, $value, $expected, $config) {
    $listColumn = new ListColumn($columnName, 'Test Column');
    $listColumn->displayAs($type, $config);

    $record = Status::factory()->create([
        $columnName => $value,
    ]);

    $columnValue = $this->listsWidget->getColumnValue($record, $listColumn);

    expect($columnValue)->toBe($expected);
})->with([
    ['status_comment', 'text', null, 'Test Value', ['default' => 'Test Value']],
    ['status_name', 'text', 'Test Value', 'Test Value', []],
    ['status_name', 'partial', "This is a test partial content\n", "This is a test partial content\n", ['path' => 'tests.admin::_partials.test-partial']],
    ['status_name', 'money', 100.1234567, '100.12', []],
    ['status_name', 'switch', 1, 'Yes', ['onText' => 'Yes']],
    ['updated_at', 'datetime', '2022-12-31 23:59:59', '31 December 2022 23:59', ['format' => 'DD MMMM YYYY HH:mm']],
    ['updated_at', 'datesince', '2022-12-31 23:59:59', '31 Dec 2022', []],
    ['updated_at', 'timesince', '2022-12-31 23:59:59', '2 years ago', []],
    ['updated_at', 'timetense', '2022-12-31 23:59:59', '31 Dec 2022 at 11:59 pm', []],
    ['updated_at', 'time', '23:59:59', '23:59', ['format' => 'HH:mm']],
    ['updated_at', 'date', '2022-12-31', '31 December 2022', ['format' => 'DD MMMM YYYY']],
    ['status_name', 'currency', 100, '£100.00', []],
    ['updated_at', 'datetime', null, null, []],
    ['updated_at', 'date', null, null, []],
    ['updated_at', 'time', null, null, []],
    ['updated_at', 'datesince', null, null, []],
    ['updated_at', 'timesince', null, null, []],
    ['updated_at', 'timetense', null, null, []],
]);

it('returns column value from formatter', function() {
    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->formatter = function($value): string {
        return 'Formatted Value';
    };
    $record = Status::factory()->create([
        'status_name' => 'Test Value',
    ]);

    $columnValue = $this->listsWidget->getColumnValue($record, $listColumn);

    expect($columnValue)->toBe('Formatted Value');
});

it('returns null list column value from unloaded model relation', function() {
    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->displayAs('text', ['relation' => 'status_history']);

    $record = Status::factory()->create();

    expect($this->listsWidget->getColumnValue($record, $listColumn))->toBeNull();
});

it('returns list column value from model relation', function() {
    $listColumn = new ListColumn('status_id', 'Test Column');
    $listColumn->displayAs('text', ['relation' => 'status_history']);

    $status = Status::factory()->create();
    $status->setRelation('status_history', StatusHistory::factory()->create([
        'status_id' => 1,
    ]));

    $columnValue = $this->listsWidget->getColumnValue($status, $listColumn);

    expect($columnValue)->toBe('1');
});

it('returns list column value from model pivot relation', function() {
    $this->listsWidget->model = new User;
    $listColumn = new ListColumn('location_name', 'Test Column');
    $listColumn->displayAs('text', ['relation' => 'pivot']);

    $user = User::factory()->create();
    $location = Location::factory()->create();
    $user->setRelation('pivot', $location);
    $user->locations()->attach($location);

    $columnValue = $this->listsWidget->getColumnValue($user, $listColumn);

    expect($columnValue)->toBe($location->location_name);
});

it('overrides list column value using event', function() {
    $this->listsWidget->bindEvent('list.overrideColumnValue', function($column, $record, $value): string {
        return 'Overridden Value';
    });
    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->displayAs('text');

    $record = Status::factory()->create();
    $columnValue = $this->listsWidget->getColumnValue($record, $listColumn);

    expect($columnValue)->toBe('Overridden Value');
});

it('overrides button attributes using event', function() {
    $this->listsWidget->bindEvent('list.overrideColumnValue', function($column, $record, $attributes): array {
        return [
            'title' => 'Overridden Title',
            'url' => 'model/edit',
        ];
    });
    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->displayAs('text', ['attributes' => ['class' => 'btn btn-primary', 'href' => 'model/edit']]);

    $record = Status::factory()->create();
    $buttonAttributes = $this->listsWidget->getButtonAttributes($record, $listColumn);

    expect($buttonAttributes)->toBe(' title="Overridden Title" href="model/edit"');
});

it('throws exception with datetime value is invalid', function() {
    $listColumn = new ListColumn('status_name', 'Test Column');
    $listColumn->displayAs('datetime');
    $record = Status::factory()->create([
        'status_name' => 'Invalid Date',
    ]);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid date value supplied to DateTime helper.');

    $this->listsWidget->getColumnValue($record, $listColumn);
});

it('throws exception when model does not have relation', function() {
    $listColumn = new ListColumn('status_id', 'Test Column');
    $listColumn->displayAs('text', ['relation' => 'invalid_relation']);
    $status = Status::factory()->create();
    $status->setRelation('invalid_relation', StatusHistory::factory()->create([
        'status_id' => 1,
    ]));

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.alert_missing_model_definition'), Status::class, 'invalid_relation'));

    $this->listsWidget->getColumnValue($status, $listColumn);
});

it('adds filter', function() {
    $calledFilter = false;

    $this->listsWidget->addFilter(function($query) use (&$calledFilter) {
        $calledFilter = true;
    });

    $this->listsWidget->prepareVars();

    expect($calledFilter)->toBeTrue();
});

it('handles onSort action', function() {
    $this->listsWidget->defaultSort = 'status_id desc';
    $this->listsWidget->columns['status_id']['sortable'] = true;
    expect($this->listsWidget->onSort())->toBeArray();

    request()->query->add(['sort_by' => 'status_id']);

    $this->listsWidget->prepareVars();

    expect($this->listsWidget->vars['sortDirection'])->toBe('desc');

    $this->listsWidget->onSort();

    expect($this->listsWidget->vars['sortDirection'])->toBe('asc');

    $this->listsWidget->onSort();

    expect($this->listsWidget->vars['sortDirection'])->toBe('desc');
});

it('handles onLoadSetup action', function() {
    $this->listsWidget->pageLimit = 200;
    $loadSetupResult = $this->listsWidget->onLoadSetup();

    expect($loadSetupResult)
        ->toBeArray()
        ->toHaveKey('#'.$this->listsWidget->getId().'-setup-modal-content');
});

it('handles onApplySetup action', closure: function() {
    request()->request->add([
        'visible_columns' => $visibleColumns = ['status_id', 'name'],
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

it('throws an exception when override column is defined', function() {
    request()->request->add([
        'visible_columns' => ['status_name'],
    ]);

    $this->listsWidget->prepareVars();

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.list.invalid_column_override'), 'status_name'));

    $this->listsWidget->onApplySetup();
});

it('handles onResetSetup action', function() {
    $this->listsWidget->putSession('visible', ['status_id', 'status_name']);
    $this->listsWidget->putSession('order', ['status_id', 'status_name']);
    $this->listsWidget->putSession('page_limit', 20);

    $this->listsWidget->onResetSetup();

    expect($this->listsWidget->getSession('visible'))->toBeNull()
        ->and($this->listsWidget->getSession('order'))->toBeNull()
        ->and($this->listsWidget->getSession('page_limit'))->toBeNull();

});

it('handles onBulkAction action', function() {
    request()->query->add([
        'code' => 'delete',
        'checked' => [
            $status1 = Status::factory()->create()->getKey(),
            $status2 = Status::factory()->create()->getKey(),
            $status3 = Status::factory()->create()->getKey(),
        ],
    ]);

    $this->listsWidget->prepareVars();

    $this->listsWidget->onBulkAction();

    expect($this->listsWidget->renderBulkActionButton('checked'))->toBe('checked');

    $this->assertDatabaseMissing('statuses', ['status_id' => $status1]);
    $this->assertDatabaseMissing('statuses', ['status_id' => $status2]);
    $this->assertDatabaseMissing('statuses', ['status_id' => $status3]);
});

it('filters restricted and location aware bulk action buttons', function() {
    LocationFacade::setModel(Location::factory()->create());
    $this->listsWidget->bulkActions = [
        'delete' => [
            'label' => 'Delete',
            'permissions' => ['Admin.Statuses'],
        ],
        'status' => [
            'label' => 'Status',
            'locationAware' => true,
        ],
    ];

    $this->listsWidget->prepareVars();

    expect($this->listsWidget->vars['bulkActions'])->toHaveCount(0);
});

it('throws exception when code is missing in request', function() {
    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(lang('igniter::admin.list.missing_action_code'));

    $this->listsWidget->onBulkAction();
});

it('throws exception when bulk action code is invalid', function() {
    request()->query->add(['code' => 'invalid']);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.list.action_not_found'), 'invalid'));

    $this->listsWidget->onBulkAction();
});

it('throws exception when checked value is not an array', function() {
    request()->query->add(['code' => 'delete', 'checked' => 'invalid']);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(lang('igniter::admin.list.delete_empty'));

    $this->listsWidget->onBulkAction();
});

it('handles onBulkAction action deletes all records', function() {
    request()->query->add([
        'code' => 'delete',
        'select_all' => '1',
        'checked' => [
            $status1 = Status::factory()->create()->getKey(),
        ],
    ]);

    $this->listsWidget->prepareVars();

    $this->listsWidget->onBulkAction();

    $this->assertDatabaseMissing('statuses', ['status_id' => $status1]);
});

it('throws exception when bulk action widget class does not exists', function() {
    $this->widgetConfig['bulkActions'] = [
        'invalidAction' => [
            'label' => 'Invalid Action',
            'type' => 'invalid',
        ],
    ];

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.alert_widget_class_name'), 'invalidAction'));

    $listsWidget = new Lists($this->controller, $this->widgetConfig);
    $listsWidget->bindToController();

    $listsWidget->prepareVars();
});
