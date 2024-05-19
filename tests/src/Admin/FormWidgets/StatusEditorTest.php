<?php

namespace Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\StatusEditor;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Cart\Models\Order;
use Igniter\System\Facades\Assets;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\View\Factory;

dataset('initialization', [
    ['formTitle', 'igniter::admin.statuses.text_editor_title'],
    ['statusArrayName', 'statusData'],
    ['statusFormName', 'Status'],
    ['statusKeyFrom', 'status_id'],
    ['statusNameFrom', 'status_name'],
    ['statusModelClass', StatusHistory::class],
    ['statusColorFrom', 'status_color'],
    ['statusRelationFrom', 'status'],
    ['assigneeFormName', 'Assignee'],
    ['assigneeArrayName', 'assigneeData'],
    ['assigneeKeyFrom', 'assignee_id'],
    ['assigneeGroupKeyFrom', 'assignee_group_id'],
    ['assigneeGroupNameFrom', 'user_group_name'],
    ['assigneeRelationFrom', 'assignee'],
    ['assigneeNameFrom', 'name'],
    ['assigneeOrderPermission', 'Admin.AssignOrders'],
    ['assigneeReservationPermission', 'Admin.AssignReservations'],
]);

beforeEach(function() {
    $this->controllerMock = $this->createMock(AdminController::class);
    $this->formField = new FormField('test_field', 'RichEditor');
    $this->statusEditorWidget = new StatusEditor($this->controllerMock, $this->formField, [
        'model' => Order::factory()->create(),
        'form' => [
            'fields' => [
                'status_id' => [],
                'comment' => [],
                'assignee_id' => [],
            ],
        ],
    ]);
});

it('initializes correctly', function($property, $expected) {
    expect($this->statusEditorWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    Assets::shouldReceive('addJs')->once()->with('statuseditor.js', 'statuseditor-js');

    $this->statusEditorWidget->assetPath = [];

    $this->statusEditorWidget->loadAssets();
});

it('prepares variables correctly', function() {
    $this->statusEditorWidget->prepareVars();

    expect($this->statusEditorWidget->vars)
        ->toBeArray()
        ->toHaveKey('field')
        ->toHaveKey('status')
        ->toHaveKey('assignee');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('repeater/repeater'));

    $this->repeaterWidget->render();
})->throws(\Exception::class);

it('gets save value correctly', function() {
    expect($this->statusEditorWidget->getSaveValue(null))->toBe(FormField::NO_SAVE_DATA);
});

it('loads status without errors', function() {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'recordId' => 'load-status',
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('getPathInfo')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('root')->andReturn('localhost');
    $mockRequest->shouldReceive('getScheme')->andReturn('https');
    $mockRequest->shouldReceive('input')->andReturn('');
    app()->instance('request', $mockRequest);

    $result = $this->statusEditorWidget->onLoadRecord();

    expect($result)->toBeString();
});

it('updates status without errors', function() {
    Event::fake();

    $user = User::factory()->create();
    $status = Status::factory()->create();
    $selectedStatus = Status::factory()->create();
    $this->statusEditorWidget->model->status_id = $status->getKey();

    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'context' => 'status',
        'statusData' => [
            'status_id' => $selectedStatus->getKey(),
            'comment' => 'Test new comment',
        ],
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    app()->instance('request', $mockRequest);

    $this->controllerMock->method('getUser')->willReturn($user);

    expect($this->statusEditorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('status_history', [
        'object_id' => $this->statusEditorWidget->model->getKey(),
        'object_type' => 'orders',
        'status_id' => $selectedStatus->getKey(),
        'comment' => 'Test new comment',
        'user_id' => $user->getKey(),
    ]);
});

it('loads assignee without errors', function() {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'recordId' => 'load-assignee',
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('getPathInfo')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('root')->andReturn('localhost');
    $mockRequest->shouldReceive('getScheme')->andReturn('https');
    $mockRequest->shouldReceive('input')->andReturn('');
    app()->instance('request', $mockRequest);

    $result = $this->statusEditorWidget->onLoadRecord();

    expect($result)->toBeString();
});

it('updates assignee without errors', function() {
    Event::fake();

    $user = User::factory()->create(['super_user' => 1]);
    $assignee = User::factory()->create();
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'context' => 'assignee',
        'assigneeData' => [
            'assignee_id' => $assignee->getKey(),
        ],
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    app()->instance('request', $mockRequest);

    $this->controllerMock->method('getUser')->willReturn($user);

    expect($this->statusEditorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('assignable_logs', [
        'assignable_id' => $this->statusEditorWidget->model->getKey(),
        'assignable_type' => 'orders',
        'assignee_id' => $assignee->getKey(),
    ]);
});

it('loads selected status without errors', function() {
    $status = Status::factory()->create();
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'statusId' => $status->getKey(),
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    app()->instance('request', $mockRequest);

    $result = $this->statusEditorWidget->onLoadStatus();

    expect($result)->toBeArray()
        ->and($result['status_id'])->toBe($status->getKey());
});

it('loads assignee list without errors', function() {
    $assigneeGroup = UserGroup::factory()->create();
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'groupId' => $assigneeGroup->getKey(),
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    app()->instance('request', $mockRequest);

    expect($this->statusEditorWidget->onLoadAssigneeList())->toBeArray();
});