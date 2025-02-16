<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Form;
use Igniter\System\Models\Currency;
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

        public function formValidate($model, $formWidget)
        {
            return $formWidget->getSaveData() ?: false;
        }
    };
    $this->controller->widgets['toolbar'] = new \Igniter\Admin\Widgets\Toolbar($this->controller);
    $this->formController = new FormController($this->controller);
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

    $formConfig = $this->formController->getConfig();
    $formConfig['request'] = \Igniter\Admin\Http\Requests\StatusRequest::class;
    $this->formController->setConfig($formConfig);

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

it('create_onSave returns null when controller level form validation fails', function() {
    $response = $this->formController->create_onSave();

    expect($response)->toBeNull();
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

it('edits an existing record with relationship', function() {
    $currency = Currency::factory()->create();
    $recordId = $currency->getKey();
    $context = 'edit';
    request()->request->add([
        'Currency' => [
            'currency_name' => 'New currency - '.$recordId,
            'currency_code' => 'GBP',
            'currency_rate' => 1.0,
            'country_id' => 1,
            'symbol_position' => '1',
            'currency_symbol' => '£',
            'thousand_sign' => ',',
            'decimal_sign' => '.',
            'decimal_position' => '2',
            'currency_status' => 1,
            'country' => [
                'country_id' => 1,
            ],
        ],
    ]);

    $controller = new class extends AdminController
    {
        public array $implement = [
            \Igniter\Admin\Http\Actions\FormController::class,
        ];

        public $formConfig = [
            'name' => 'Controller',
            'model' => Currency::class,
            'edit' => [
                'title' => 'Edit record',
                'redirect' => 'path/edit/{id}',
                'redirectClose' => 'path',
                'redirectNew' => 'path/create',
            ],
            'configFile' => [
                'form' => [
                    'toolbar' => [
                        'buttons' => [],
                    ],
                    'fields' => [
                        'currency_name' => [],
                        'currency_code' => [],
                        'country_id' => [],
                        'currency_rate' => [],
                        'symbol_position' => [],
                        'currency_symbol' => [],
                        'thousand_sign' => [],
                        'decimal_sign' => [],
                        'decimal_position' => [],
                        'currency_status' => [],
                        'country' => [],
                    ],
                ],
            ],
        ];
    };
    $formController = new FormController($controller);
    $response = $formController->edit_onSave($context, $recordId);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
});

it('edit_onSave returns null when controller level form validation fails', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'edit';
    $response = $this->formController->edit_onSave($context, $recordId);

    expect($response)->toBeNull();
});

it('deletes an existing record', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'edit';

    $response = $this->formController->edit_onDelete($context, $recordId);

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    $this->assertDatabaseMissing('statuses', ['status_id' => $recordId]);
});

it('returns error when deleting fails', function() {
    $record = Status::factory()->create();
    $recordId = $record->getKey();
    $context = 'edit';

    Status::deleting(function(): false {
        return false;
    });

    $response = $this->formController->edit_onDelete($context, $recordId);

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    $this->assertDatabaseHas('statuses', ['status_id' => $recordId]);
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

it('binds events correctly', function() {
    $recordId = Status::factory()->create()->getKey();
    $context = 'edit';
    $controller = new class extends AdminController
    {
        public array $implement = [
            \Igniter\Admin\Http\Actions\FormController::class,
        ];

        public $formConfig = [
            'name' => 'Controller',
            'model' => Status::class,
            'edit' => [
                'title' => 'Edit record',
                'redirect' => 'path/edit/{id}',
                'redirectClose' => 'path',
                'redirectNew' => 'path/create',
            ],
            'configFile' => [
                'form' => [
                    'toolbar' => [
                        'buttons' => [],
                    ],
                    'fields' => [
                        'status_name' => [],
                        'status_for' => [],
                        'status_color' => [],
                        'status_comment' => [],
                        'notify_customer' => [],
                    ],
                ],
            ],
        ];

        public function formExtendRefreshData(Form $host, array $saveData): array
        {
            return [];
        }
    };
    $formController = new FormController($controller);
    $formController->edit($context, $recordId);

    expect($controller->widgets['form']->onRefresh())->toBeArray();
});

it('renders form correctly', function() {
    $recordId = Status::factory()->create()->getKey();
    $context = 'edit';
    $this->formController->edit($context, $recordId);

    expect($this->formController->renderForm())->toBeString()
        ->and($this->controller->widgets['form']->onRefresh())->toBeArray();
});

it('renders form toolbar correctly', function() {
    $recordId = Status::factory()->create()->getKey();
    $context = 'edit';
    $this->formController->edit($context, $recordId);

    expect($this->formController->renderFormToolbar())->toBeString();
});

it('appends new to redirect context when post new is true', function() {
    request()->request->add(['new' => 1]);
    $this->formController->setConfig(['context' => ['redirectNew' => 'redirect-url']]);

    $result = $this->formController->makeRedirect('context');

    expect($result->getTargetUrl())->toBe(admin_url('redirect-url'));
});

it('appends close to redirect context when post close is true', function() {
    request()->request->add(['close' => 1]);
    $this->formController->setConfig(['context' => ['redirectClose' => 'redirect-url']]);

    $result = $this->formController->makeRedirect('context');

    expect($result->getTargetUrl())->toBe(admin_url('redirect-url'));
});

it('refreshes controller when post refresh is true', function() {
    request()->request->add(['refresh' => 1]);

    $result = $this->formController->makeRedirect('context');

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});
