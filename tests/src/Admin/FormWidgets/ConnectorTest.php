<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Connector;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Connector');
    $this->formField->displayAs('connector');

    $this->formField->arrayName = 'status';
    $this->connectorWidget = new Connector($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'form' => [
            'fields' => [
                'object_id' => [],
                'object_type' => [],
                'user_id' => [],
                'status_id' => [],
                'notify' => [],
                'comment' => [],
            ],
        ],
    ]);
});

it('initializes correctly', function() {
    $this->formField->disabled = true;

    $this->connectorWidget->initialize();

    expect($this->connectorWidget->editable)->toBeTrue()
        ->and($this->connectorWidget->sortable)->toBeFalse()
        ->and($this->connectorWidget->previewMode)->toBeTrue();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/repeater.js', 'repeater-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    Assets::shouldReceive('addJs')->once()->with('connector.js', 'connector-js');

    $this->connectorWidget->assetPath = [];

    $this->connectorWidget->loadAssets();
});

it('renders correctly', function() {
    expect($this->connectorWidget->render())->toBeString();
});

it('prepares vars correctly', function() {
    $this->connectorWidget->prepareVars();

    expect($this->connectorWidget->vars)
        ->toHaveKey('formField')
        ->toHaveKey('fieldItems')
        ->toHaveKey('editable')
        ->toHaveKey('sortable')
        ->toHaveKey('nameFrom')
        ->toHaveKey('partial')
        ->toHaveKey('descriptionFrom')
        ->toHaveKey('sortableInputName')
        ->toHaveKey('newRecordTitle')
        ->toHaveKey('emptyMessage')
        ->toHaveKey('confirmMessage');
});

it('processes existing collection records on render', function() {
    $this->connectorWidget->sortable = true;
    $statuses = Status::factory()->count(2)->create();
    $this->formField->value = $statuses;

    $this->connectorWidget->prepareVars();

    expect($this->connectorWidget->vars['fieldItems']->count())->toBe(2);
});

it('processes existing array records on render', function() {
    $this->connectorWidget->sortable = true;
    $statuses = [
        ['status_id' => 1, 'priority' => 1],
        ['status_id' => 2, 'priority' => 0],
    ];
    $this->formField->value = $statuses;

    $this->connectorWidget->prepareVars();

    expect($this->connectorWidget->vars['fieldItems'])->toHaveCount(2);
});

it('returns no save data when not sortable', function() {
    $this->connectorWidget->sortable = false;

    $result = $this->connectorWidget->getSaveValue([]);

    expect($result)->toBe(FormField::NO_SAVE_DATA);
});

it('returns processed save value when sortable', function() {
    $statuses = Status::factory()->count(2)->create();
    request()->request->add(['___dragged_status_history' => $statuses->pluck('status_id')->all()]);
    $this->formField->value = $statuses;
    $this->connectorWidget->sortable = true;

    $result = $this->connectorWidget->getSaveValue([]);

    expect($result)->toBe($statuses->map(fn($status, $index): array => [
        'status_id' => $status->getKey(),
        'priority' => $index,
    ])->all());
});

it('returns empty results when no sortable field', function() {
    $this->connectorWidget->sortable = true;
    $result = $this->connectorWidget->getSaveValue([]);

    expect($result)->toBeArray()->toBeEmpty();
});

it('does not sort records when value is not a collection', function() {
    $this->connectorWidget->sortable = true;
    request()->request->add(['___dragged_status_history' => [1, 2]]);
    $this->formField->value = [
        ['status_id' => 1, 'priority' => 1],
        ['status_id' => 2, 'priority' => 0],
    ];

    $result = $this->connectorWidget->getSaveValue([]);

    expect($result)->toBeArray()->not->toBeEmpty();
});

it('refreshes widget with existing record', function() {
    $statusHistory = StatusHistory::factory()->create();
    request()->request->add(['recordId' => $statusHistory->getKey()]);

    $result = $this->connectorWidget->onRefresh();

    expect($result)->toBeArray();
});

it('loads new record correctly', function() {
    expect($this->connectorWidget->onLoadRecord())->toBeString();
});

it('loads existing record correctly', function() {
    $statusHistory = StatusHistory::factory()->create();
    request()->request->add(['recordId' => $statusHistory->getKey()]);

    expect($this->connectorWidget->onLoadRecord())->toBeString();
});

it('creates a record correctly', function() {
    request()->request->add(['status' => ['connectorData' => [
        'object_id' => 1,
        'object_type' => 'order',
        'user_id' => 1,
        'status_id' => 1,
        'notify' => 1,
        'comment' => 'Test commment',
    ]]]);

    expect($this->connectorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('status_history', [
        'status_id' => $this->connectorWidget->model->getKey(),
        'object_type' => 'order',
        'notify' => 1,
        'comment' => 'Test commment',
    ]);
});

it('updates a record correctly', function() {
    $statusHistory = StatusHistory::factory()->create();
    request()->request->add([
        'recordId' => $statusHistory->getKey(),
        'status' => [
            'connectorData' => [
                'object_id' => 1,
                'object_type' => 'order',
                'user_id' => 1,
                'status_id' => $statusHistory->status_id,
                'notify' => 1,
                'comment' => 'Test commment',
            ],
        ],
    ]);

    expect($this->connectorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('status_history', [
        'status_id' => $statusHistory->status_id,
        'object_type' => 'order',
        'notify' => 1,
        'comment' => 'Test commment',
    ]);
});

it('returns false when record ID is missing', function() {
    expect($this->connectorWidget->onDeleteRecord())->toBeFalse();
});

it('throws exception when record is not found', function() {
    request()->request->add(['recordId' => 123]);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.form.not_found'), 123));

    $this->connectorWidget->onDeleteRecord();
});

it('deletes a record correctly', function() {
    $statusHistory = StatusHistory::factory()->create();
    request()->request->add([
        'recordId' => $statusHistory->getKey(),
        'status' => [
            'connectorData' => [
                'object_id' => 1,
                'object_type' => 'order',
                'user_id' => 1,
                'status_id' => 1,
                'notify' => 1,
                'comment' => 'Test commment',
            ],
        ],
    ]);

    $this->connectorWidget->onDeleteRecord();

    $this->assertDatabaseMissing('status_history', [
        'status_history_id' => $statusHistory->getKey(),
    ]);
});
