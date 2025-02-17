<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\ListController;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Lists;
use Igniter\Admin\Widgets\Toolbar;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
        public array $implement = [
            ListController::class,
        ];

        public $listConfig = [
            'list' => [
                'model' => Status::class,
                'title' => 'List records',
                'emptyMessage' => 'No records found',
                'defaultSort' => ['status_id', 'DESC'],
                'back' => 'admin/statuses',
                'configFile' => [
                    'list' => [
                        'toolbar' => [
                            'buttons' => [],
                        ],
                        'filter' => [
                            'scopes' => [
                                'status_for' => [
                                    'label' => 'Status for',
                                    'type' => 'select',
                                    'conditions' => 'status_for = :filtered',
                                    'options' => [
                                        'order' => 'Order',
                                        'reservation' => 'Reservation',
                                    ],
                                ],
                            ],
                            'search' => [
                                'prompt' => 'Search records',
                            ],
                        ],
                        'columns' => [
                            'status_name' => [],
                            'status_comment' => [],
                            'status_for' => [],
                            'status_color' => [],
                            'notify_customer' => [],
                        ],
                    ],
                ],
            ],
        ];
    };
    $this->listController = new ListController($this->controller);
});

it('runs index action correctly', function() {
    AdminMenu::shouldReceive('setPreviousUrl')->once();

    $this->listController->index();

    expect($this->listController->getListWidget())->toBeInstanceOf(Lists::class);
});

it('flashes warning message when no checked ids are provided', function() {
    $this->listController->index_onDelete();

    expect(flash()->messages()->first())->level->toBe('success');
});

it('flashes warning message when no records to delete', function() {
    request()->request->add([
        'checked' => [999],
    ]);

    $this->listController->index_onDelete();

    expect(flash()->messages()->first())->level->toBe('warning');
});

it('deletes records and flashes success message', function() {
    $checkedIds = [
        Status::factory()->create()->getKey(),
        Status::factory()->create()->getKey(),
        Status::factory()->create()->getKey(),
    ];

    request()->request->add([
        'checked' => $checkedIds,
    ]);

    $this->listController->index_onDelete();

    expect(Status::whereIn('status_id', $checkedIds)->count())->toBe(0)
        ->and(flash()->messages()->first())->level->toBe('success');
});

it('binds events correctly', function() {
    request()->request->add(['list_filter' => [
        'status_for' => 'order',
    ]]);

    $this->listController->index();
    $this->listController->renderList();

    $listFilterWidget = $this->controller->widgets['list_filter'];
    $query = Status::query();
    $scope = $listFilterWidget->getScope('status_for');
    $listFilterWidget->fireSystemEvent('admin.filter.extendQuery', [$query, $scope]);

    expect($this->controller->widgets['list_filterSearch']->onSubmit())->toBeArray()
        ->and($listFilterWidget->onSubmit())->toBeArray();
});

it('renders list widget correctly', function() {
    $this->controller->widgets['toolbar'] = new Toolbar($this->controller);

    $this->listController->index();

    expect($this->listController->renderList())->toBeString();
});

it('renders list toolbar correctly', function() {
    $this->controller->widgets['toolbar'] = new Toolbar($this->controller);
    $this->listController->index();

    expect($this->listController->renderListToolbar())->toBeString();
});

it('renders list filter correctly', function() {
    $this->listController->index();

    expect($this->listController->renderListFilter())->toBeString()
        ->and($this->listController->renderListFilter('invalid-alias'))->toBeNull();
});

it('can refresh list', function() {
    $this->listController->index();
    $response = $this->listController->refreshList();
    $listWidget = $this->listController->getListWidget();

    expect($response)
        ->toBeArray()
        ->toHaveKey('~#'.$listWidget->getId('list'));
});

it('returns list config correctly', function() {
    $this->listController->index();

    expect($this->listController->getListConfig())->toBeArray();
});
