<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\StatusEditor;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Widgets\Form;
use Igniter\Cart\Models\Order;
use Igniter\Flame\Exception\FlashException;
use Igniter\Local\Facades\Location;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'RichEditor');
    $this->statusEditorWidget = new StatusEditor($this->controller, $this->formField, [
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

it('initializes correctly', function() {
    expect($this->statusEditorWidget->formTitle)->toBe('igniter::admin.statuses.text_editor_title')
        ->and($this->statusEditorWidget->statusArrayName)->toBe('statusData')
        ->and($this->statusEditorWidget->statusFormName)->toBe('Status')
        ->and($this->statusEditorWidget->statusKeyFrom)->toBe('status_id')
        ->and($this->statusEditorWidget->statusNameFrom)->toBe('status_name')
        ->and($this->statusEditorWidget->statusModelClass)->toBe(StatusHistory::class)
        ->and($this->statusEditorWidget->statusColorFrom)->toBe('status_color')
        ->and($this->statusEditorWidget->statusRelationFrom)->toBe('status')
        ->and($this->statusEditorWidget->assigneeFormName)->toBe('Assignee')
        ->and($this->statusEditorWidget->assigneeArrayName)->toBe('assigneeData')
        ->and($this->statusEditorWidget->assigneeKeyFrom)->toBe('assignee_id')
        ->and($this->statusEditorWidget->assigneeGroupKeyFrom)->toBe('assignee_group_id')
        ->and($this->statusEditorWidget->assigneeGroupNameFrom)->toBe('user_group_name')
        ->and($this->statusEditorWidget->assigneeRelationFrom)->toBe('assignee')
        ->and($this->statusEditorWidget->assigneeNameFrom)->toBe('name')
        ->and($this->statusEditorWidget->assigneeOrderPermission)->toBe('Admin.AssignOrders')
        ->and($this->statusEditorWidget->assigneeReservationPermission)->toBe('Admin.AssignReservations');
});

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
    expect($this->statusEditorWidget->render())->toBeString();
});

it('gets save value correctly', function() {
    expect($this->statusEditorWidget->getSaveValue(null))->toBe(FormField::NO_SAVE_DATA);
});

it('loads status without errors', function() {
    request()->request->add([
        'recordId' => 'load-status',
    ]);

    $result = $this->statusEditorWidget->onLoadRecord();

    expect($result)->toBeString();
});

it('updates status without errors', function() {
    Event::fake();

    $user = User::factory()->create();
    $this->controller->setUser($user);
    $status = Status::factory()->create();
    $selectedStatus = Status::factory()->create();
    $this->statusEditorWidget->model->status_id = $status->getKey();
    request()->request->add([
        'context' => 'status',
        'statusData' => [
            'status_id' => $selectedStatus->getKey(),
            'comment' => 'Test new comment',
        ],
    ]);

    expect($this->statusEditorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('status_history', [
        'object_id' => $this->statusEditorWidget->model->getKey(),
        'object_type' => 'orders',
        'status_id' => $selectedStatus->getKey(),
        'comment' => 'Test new comment',
        'user_id' => $user->getKey(),
    ]);
});

it('updates status fails with errors', function() {
    Event::fake();

    $user = User::factory()->create();
    $this->controller->setUser($user);
    $status = Status::factory()->create();
    $this->statusEditorWidget->model->status_id = $status->getKey();
    request()->request->add([
        'context' => 'status',
        'statusData' => [
            'status_id' => 123,
            'comment' => 'Test new comment',
        ],
    ]);

    expect($this->statusEditorWidget->onSaveRecord())->toBeArray();
});

it('loads assignee without errors', function() {
    request()->request->add([
        'recordId' => 'load-assignee',
    ]);

    $result = $this->statusEditorWidget->onLoadRecord();

    expect($result)->toBeString();
});

it('updates assignee without errors', function() {
    Event::fake();

    $user = User::factory()->create(['super_user' => 1]);
    $this->controller->setUser($user);
    $assignee = User::factory()->create();
    request()->request->add([
        'context' => 'assignee',
        'assigneeData' => [
            'assignee_id' => $assignee->getKey(),
        ],
    ]);

    expect($this->statusEditorWidget->onSaveRecord())->toBeArray();

    $this->assertDatabaseHas('assignable_logs', [
        'assignable_id' => $this->statusEditorWidget->model->getKey(),
        'assignable_type' => 'orders',
        'assignee_id' => $assignee->getKey(),
    ]);
});

it('updates assignee fails when user is not permitted', function() {
    Event::fake();

    $user = User::factory()->create();
    $this->controller->setUser($user);
    request()->request->add([
        'context' => 'assignee',
    ]);

    expect(fn() => $this->statusEditorWidget->onSaveRecord())
        ->toThrow(FlashException::class, lang('igniter::admin.alert_user_restricted'));
});

it('loads selected status without errors', function() {
    $status = Status::factory()->create();
    request()->request->add([
        'statusId' => $status->getKey(),
    ]);
    $result = $this->statusEditorWidget->onLoadStatus();

    expect($result)->toBeArray()
        ->and($result['status_id'])->toBe($status->getKey());
});

it('loads assignee list without errors', function() {
    $assigneeGroup = UserGroup::factory()->create();
    request()->request->add([
        'groupId' => $assigneeGroup->getKey(),
    ]);

    expect($this->statusEditorWidget->onLoadAssigneeList())->toBeArray();
});

it('returns empty array when groupId is not provided', function() {
    $form = new class extends Form
    {
        public function __construct() {}
    };

    $result = StatusEditor::getAssigneeOptions($form, 'field');

    expect($result)->toBe([]);
});

it('returns assignee options when groupId is provided', function() {
    $form = new class extends Form
    {
        public function __construct() {}

        public function getField($field): ?FormField
        {
            $formField = new FormField('assignee_group_id', 'select');
            $formField->value = '1';

            return $formField;
        }
    };
    request()->request->add(['groupId' => '1']);

    Location::shouldReceive('currentOrAssigned')->andReturn([1, 2]);

    $result = StatusEditor::getAssigneeOptions($form, 'field');

    expect($result)->toBeCollection();
});

it('returns empty array when no locations are assigned', function() {
    $form = new class extends Form
    {
        public function __construct() {}

        public function getField($field): ?FormField
        {
            $formField = new FormField('assignee_group_id', 'select');
            $formField->value = '1';

            return $formField;
        }
    };
    request()->request->add(['groupId' => '1']);

    Location::shouldReceive('currentOrAssigned')->andReturn([]);

    $result = StatusEditor::getAssigneeOptions($form, 'field');

    expect($result)->toBeCollection();
});

it('returns all user group options for super user', function() {
    $user = User::factory()->superUser()->create();
    AdminAuth::setUser($user);

    $result = StatusEditor::getAssigneeGroupOptions();

    expect($result)->toBeCollection();
});

it('returns user group options for non-super user', function() {
    $user = User::factory()->create();
    AdminAuth::setUser($user);

    $result = StatusEditor::getAssigneeGroupOptions();

    expect($result)->toBeCollection();
});
