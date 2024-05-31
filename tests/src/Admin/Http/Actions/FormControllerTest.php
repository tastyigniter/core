<?php

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Models\Status;
use Igniter\System\Classes\FormRequest;
use Illuminate\Http\RedirectResponse;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
        public array $implement = [
            \Igniter\Admin\Http\Actions\FormController::class,
        ];

        public $formConfig = [
            'name' => 'Controller',
            'model' => \Igniter\Admin\Models\Status::class,
            'create' => [
                'title' => 'Create record',
                'redirect' => 'path/edit/{id}',
                'redirectClose' => 'path',
                'redirectNew' => 'path/create',
            ],
            'edit' => [
                'title' => 'Edit record',
                'redirect' => 'path/edit/{id}',
                'redirectClose' => 'path',
                'redirectNew' => 'path/create',
            ],
            'preview' => [
                'title' => 'Preview record',
                'back' => 'path',
            ],
            'delete' => [
                'redirect' => 'path',
            ],
            'configFile' => [
                'form' => [
                    'toolbar' => [
                        'buttons' => [],
                    ],
                    'fields' => [
                        'status_name' => [],
                        'status_comment' => [],
                        'status_for' => [],
                        'status_color' => [],
                        'notify_customer' => [],
                    ],
                ],
            ],
        ];
    };
    $this->formController = new FormController($this->controller);

    $this->formRequestMock = $this->createMock(FormRequest::class);
    app()->instance('TestRequest', $this->formRequestMock);
});

it('initializes form with model and context', function() {
    $model = Status::factory()->create();
    $context = 'create';

    $this->formController->initForm($model, $context);

    expect($this->formController->getFormModel())->toBe($model)
        ->and($this->formController->getFormContext())->toBe($context);
});

it('runs the create action', function() {
    $this->formController->create();

    expect($this->formController->getFormModel())->toBeInstanceOf(Status::class)
        ->and($this->formController->getFormContext())->toBe('create');
});

it('creates a new record', function() {
    request()->request->add([
        'Status' => [
            'status_name' => 'New status',
            'status_comment' => 'Test comment new record',
            'status_for' => 'order',
            'status_color' => '#000000',
            'notify_customer' => 1,
        ],
    ]);

    $response = $this->formController->create_onSave();

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($this->formController->getFormModel())->toBeInstanceOf(Status::class)
        ->and($this->formController->getFormContext())->toBe('create');

    $this->assertDatabaseHas('statuses', [
        'status_name' => 'New status',
        'status_comment' => 'Test comment new record',
        'status_for' => 'order',
        'status_color' => '#000000',
        'notify_customer' => 1,
    ]);
});

it('runs the edit action', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();

    $this->formController->edit('edit', $recordId);

    expect($this->formController->getFormModel())->toBeInstanceOf(Status::class)
        ->and($this->formController->getFormModel()->getKey())->toBe($recordId)
        ->and($this->formController->getFormContext())->toBe('edit', $recordId);
});

it('edits an existing record', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'edit';

    request()->request->add([
        'Status' => [
            'status_name' => 'New status - '.$recordId,
            'status_comment' => 'Test comment new record',
            'status_for' => 'order',
            'status_color' => '#000000',
            'notify_customer' => 1,
        ],
    ]);

    $response = $this->formController->edit_onSave($context, $recordId);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($this->formController->getFormModel())->toBeInstanceOf(Status::class)
        ->and($this->formController->getFormContext())->toBe($context);

    $this->assertDatabaseHas('statuses', [
        'status_name' => 'New status - '.$recordId,
        'status_comment' => 'Test comment new record',
        'status_for' => 'order',
        'status_color' => '#000000',
        'notify_customer' => 1,
    ]);
});

it('deletes an existing record', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'edit';

    $response = $this->formController->edit_onDelete($context, $recordId);

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    $this->assertDatabaseMissing('statuses', ['status_id' => $recordId]);
});

it('previews an existing record', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'preview';

    $this->formController->preview($context, $recordId);

    expect($this->formController->getFormModel())->toBeInstanceOf(Status::class)
        ->and($this->formController->getFormModel()->getKey())->toBe($recordId)
        ->and($this->formController->getFormContext())->toBe($context);
});
