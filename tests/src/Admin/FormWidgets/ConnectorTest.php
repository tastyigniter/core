<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Connector;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\View\Factory;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;

dataset('initialization', [
    ['editable', true],
    ['sortable', false],
]);

dataset('connectorData', [
    fn() => [
        'object_id' => 1,
        'object_type' => 'order',
        'user_id' => 1,
        'status_id' => 1,
        'notify' => 1,
        'comment' => 'Test commment',
    ],
]);

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

it('initializes correctly', function($property, $expected) {
    expect($this->connectorWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/repeater.js', 'repeater-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.js', 'recordeditor-js');
    Assets::shouldReceive('addJs')->once()->with('connector.js', 'connector-js');

    $this->connectorWidget->assetPath = [];

    $this->connectorWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('connector/connector'));

    $this->connectorWidget->render();
})->throws(\Exception::class);

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

it('loads a record correctly', function() {
    expect($this->connectorWidget->onLoadRecord())->toBeString();
});

it('creates a record correctly', function($connectorData) {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'status' => ['connectorData' => $connectorData],
    ]);
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    expect($this->connectorWidget->onSaveRecord())->toBeArray();

    $connectorData['status_id'] = $this->connectorWidget->model->getKey();
    $this->assertDatabaseHas('status_history', $connectorData);
})->with('connectorData');

it('updates a record correctly', function($connectorData) {
    $connectorData['status_id'] = $this->connectorWidget->model->getKey();
    $statusHistory = StatusHistory::factory()->create();
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'recordId' => $statusHistory->getKey(),
        'status' => ['connectorData' => $connectorData],
    ]);
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    expect($this->connectorWidget->onSaveRecord())->toBeArray();

    $connectorData['status_history_id'] = $statusHistory->getKey();
    $this->assertDatabaseHas('status_history', $connectorData);
})->with('connectorData');

it('deletes a record correctly', function($connectorData) {
    $statusHistory = StatusHistory::factory()->create();
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'recordId' => $statusHistory->getKey(),
        'status' => ['connectorData' => $connectorData],
    ]);
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    $this->connectorWidget->onDeleteRecord();

    $this->assertDatabaseMissing('status_history', [
        'status_history_id' => $statusHistory->getKey(),
    ]);
})->with('connectorData');
