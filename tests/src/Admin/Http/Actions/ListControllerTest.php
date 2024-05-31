<?php

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\ListController;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Lists;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
        public array $implement = [
            \Igniter\Admin\Http\Actions\ListController::class,
        ];

        public $listConfig = [
            'list' => [
                'model' => Status::class,
                'title' => 'List records',
                'emptyMessage' => 'No records found',
                'defaultSort' => ['status_id', 'DESC'],
                'configFile' => [
                    'list' => [
                        'toolbar' => [
                            'buttons' => [],
                        ],
                        'filter' => [
                            'scopes' => [],
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

it('runs index action', function() {
    $this->listController->index();
    $this->listController->renderList();

    $listWidget = $this->listController->getListWidget();

    expect($listWidget)->toBeInstanceOf(Lists::class)
        ->and($listWidget->vars['records'])->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can delete', function() {
    $checkedIds = [
        Status::factory()->create()->getKey(),
        Status::factory()->create()->getKey(),
        Status::factory()->create()->getKey(),
    ];

    request()->request->add([
        'checked' => $checkedIds,
    ]);

    $this->listController->index_onDelete();

    expect(Status::whereIn('status_id', $checkedIds)->count())->toBe(0);
});

it('can refresh list', function() {
    $this->listController->index();
    $response = $this->listController->refreshList();
    $listWidget = $this->listController->getListWidget();

    expect($response)
        ->toBeArray()
        ->toHaveKey('~#'.$listWidget->getId('list'));
});
