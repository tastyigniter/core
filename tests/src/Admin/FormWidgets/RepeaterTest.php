<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Repeater;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Widgets\Form;
use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

dataset('initialization', [
    ['sortable', false],
    ['prompt', null],
    ['sortColumnName', 'priority'],
    ['showAddButton', true],
    ['showRemoveButton', true],
    ['emptyMessage', 'lang:igniter::admin.text_empty'],
]);

dataset('repeaterData', [
    fn() => [
        'object_id' => 1,
        'object_type' => 'order',
        'user_id' => 1,
        'status_id' => 1,
        'notify' => 1,
        'comment' => 'Test comment',
    ],
]);

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Repeater');
    $this->formField->arrayName = 'status';
    $this->repeaterWidget = new Repeater($this->controller, $this->formField, [
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
    expect($this->repeaterWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('repeater.js', 'repeater-js');

    $this->repeaterWidget->assetPath = [];

    $this->repeaterWidget->loadAssets();
});

it('prepares variables correctly', function() {
    $this->repeaterWidget->prepareVars();

    expect($this->repeaterWidget->vars)
        ->toBeArray()
        ->toHaveKey('formWidgets')
        ->toHaveKey('widgetTemplate')
        ->toHaveKey('formField')
        ->toHaveKey('nextIndex')
        ->toHaveKey('prompt')
        ->toHaveKey('sortable')
        ->toHaveKey('emptyMessage')
        ->toHaveKey('showAddButton')
        ->toHaveKey('showRemoveButton')
        ->toHaveKey('indexSearch');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('repeater/repeater'));

    $this->repeaterWidget->render();
})->throws(\Exception::class);

it('gets value from model correctly', function() {
    StatusHistory::factory()->times(3)->create([
        'status_id' => $this->repeaterWidget->model->getKey(),
    ]);

    $this->repeaterWidget->model->reloadRelations();

    $value = $this->repeaterWidget->getLoadValue();

    expect($value)->toBeCollection()->toHaveCount(3);
});

it('gets value from request correctly', function($repeaterData) {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'status' => ['status_history' => [$repeaterData, $repeaterData, $repeaterData]],
    ]);
    $mockRequest->shouldReceive('path')->andReturn('admin/dashboard');
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    $value = $this->repeaterWidget->getLoadValue();

    expect($value)->toBeArray()->toHaveCount(3);
})->with('repeaterData');

it('gets save value correctly', function($repeaterData) {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        Repeater::SORT_PREFIX.$this->formField->getId() => array_flip(range(1, 3)),
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    $this->repeaterWidget->sortable = true;

    $result = $this->repeaterWidget->getSaveValue([
        $repeaterData, $repeaterData, $repeaterData,
    ]);

    expect($result)
        ->toBeArray()
        ->toHaveCount(3)
        ->and($result[0])->toHaveKey($this->repeaterWidget->sortColumnName);
})->with('repeaterData');

it('gets visible columns correctly', function($repeaterData) {
    $columns = $this->repeaterWidget->getVisibleColumns();

    expect($columns)
        ->toBeArray()
        ->toHaveKeys(array_keys($repeaterData));
})->with('repeaterData');

it('gets form widget template correctly', function() {
    $template = $this->repeaterWidget->getFormWidgetTemplate();

    expect($template)->toBeInstanceOf(Form::class);
});
