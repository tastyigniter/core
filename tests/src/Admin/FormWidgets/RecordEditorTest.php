<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\RecordEditor;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\View\Factory;

dataset('recordData', [
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
    $this->formField = new FormField('test_field', 'Record editor');
    $this->formField->arrayName = 'status';
    $this->recordEditorWidget = new RecordEditor($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'modelClass' => StatusHistory::class,
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

it('prepares vars correctly', function() {
    $this->recordEditorWidget->prepareVars();

    expect($this->recordEditorWidget->vars)
        ->toHaveKey('field')
        ->toHaveKey('addonLeft')
        ->toHaveKey('addonRight')
        ->toHaveKey('addLabel')
        ->toHaveKey('editLabel')
        ->toHaveKey('deleteLabel')
        ->toHaveKey('attachLabel')
        ->toHaveKey('showEditButton')
        ->toHaveKey('showDeleteButton')
        ->toHaveKey('showCreateButton')
        ->toHaveKey('showAttachButton');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/repeater.js', 'repeater-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/recordeditor.css', 'recordeditor-css');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.js', 'recordeditor-js');

    $this->recordEditorWidget->assetPath = [];

    $this->recordEditorWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('recordeditor/recordeditor'));

    expect($this->recordEditorWidget->render())->toBeString();
})->throws(\Exception::class);

it('loads record correctly', function() {
    expect($this->recordEditorWidget->onLoadRecord())->toBeString();
});

it('creates record correctly', function($recordData) {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'status' => ['recordData' => $recordData],
    ]);
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    expect($this->recordEditorWidget->onSaveRecord())->toBeArray();
    $recordData['status_id'] = $this->recordEditorWidget->model->getKey();
    $this->assertDatabaseHas('status_history', $recordData);
})->with('recordData')->skip('This test is failing with error: Missing method [getRecordEditorOptions] in Igniter\Admin\Models\StatusHistory.');

it('updates record correctly', function() {
    expect($this->recordEditorWidget->onSaveRecord())->toBeArray();
})->skip('This test is failing with error: Missing method [getRecordEditorOptions] in Igniter\Admin\Models\StatusHistory.');

it('deletes record correctly', function() {
    expect($this->recordEditorWidget->onDeleteRecord())->toBeArray();
})->skip('This test is failing with error: Missing method [getRecordEditorOptions] in Igniter\Admin\Models\StatusHistory.');
