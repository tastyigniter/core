<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Repeater;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Widgets\Form;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;

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
                'user_id' => [
                    'type' => 'hidden',
                ],
                'status_id' => [],
                'notify' => [],
                'comment' => [],
            ],
        ],
    ]);
});

it('initializes correctly', function() {
    expect($this->repeaterWidget->sortable)->toBeFalse()
        ->and($this->repeaterWidget->prompt)->toBeNull()
        ->and($this->repeaterWidget->sortColumnName)->toBe('priority')
        ->and($this->repeaterWidget->showAddButton)->toBeTrue()
        ->and($this->repeaterWidget->showRemoveButton)->toBeTrue()
        ->and($this->repeaterWidget->emptyMessage)->toBe('lang:igniter::admin.text_empty');
});

it('initializes correctly when related model does not exits', function() {
    $this->formField = new FormField('invalid_relation', 'Repeater');
    $this->formField->arrayName = 'status';

    $repeaterWidget = new Repeater($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'form' => [],
    ]);

    $repeaterWidget->prepareVars();

    expect($repeaterWidget->vars['widgetTemplate'])->not->toBeNull();
});

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
    StatusHistory::factory()->times(3)->create([
        'status_id' => $this->repeaterWidget->model->getKey(),
    ]);

    $this->repeaterWidget->initialize();

    expect($this->repeaterWidget->render())->toBeString();
});

it('renders correctly when existing item is a collection', function() {
    $statusHistory = StatusHistory::factory()->times(3)->create([
        'status_id' => $this->repeaterWidget->model->getKey(),
    ]);
    $this->formField->value = $statusHistory;
    request()->request->add([
        '___dragged_field-status-status-history' => range(0, 4),
    ]);

    $repeaterWidget = new Repeater($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'form' => [],
    ]);

    expect($repeaterWidget->render())->toBeString();
});

it('gets value from model correctly', function() {
    StatusHistory::factory()->times(3)->create([
        'status_id' => $this->repeaterWidget->model->getKey(),
    ]);

    $this->repeaterWidget->sortable = true;
    $this->repeaterWidget->model->reloadRelations();

    $value = $this->repeaterWidget->getLoadValue();

    expect($value)->toBeCollection()->toHaveCount(3);
});

it('gets value from request correctly', function($repeaterData) {
    request()->request->add([
        'status' => ['status_history' => [$repeaterData, $repeaterData, $repeaterData]],
    ]);

    $this->repeaterWidget->sortable = true;
    $this->repeaterWidget->initialize();

    $value = $this->repeaterWidget->getLoadValue();

    expect($value)->toBeArray()->toHaveCount(3);
})->with('repeaterData');

it('gets save value correctly', function($repeaterData) {
    request()->request->add([
        Repeater::SORT_PREFIX.$this->formField->getId() => array_flip(range(1, 3)),
    ]);

    $this->repeaterWidget->sortable = true;

    $result = $this->repeaterWidget->getSaveValue([
        $repeaterData, $repeaterData, $repeaterData,
    ]);

    expect($result)
        ->toBeArray()
        ->toHaveCount(3)
        ->and($result[0])->toHaveKey($this->repeaterWidget->sortColumnName)
        ->and($this->repeaterWidget->getSaveValue('not-an-array'))->toBe(['not-an-array']);
})->with('repeaterData');

it('returns empty visible column when field definition is missing', function() {
    $repeaterWidget = new Repeater($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'form' => [],
    ]);

    $columns = $repeaterWidget->getVisibleColumns();

    expect($columns)->toBeArray()->toBeEmpty();
});

it('gets visible columns correctly', function($repeaterData) {
    $columns = $this->repeaterWidget->getVisibleColumns();

    expect($columns)
        ->toBeArray()
        ->toHaveKeys(array_keys(array_except($repeaterData, ['user_id'])));
})->with('repeaterData');

it('gets form widget template correctly', function() {
    $repeaterWidget = new Repeater($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'form' => 'status',
    ]);

    $template = $repeaterWidget->getFormWidgetTemplate();

    expect($template)->toBeInstanceOf(Form::class);
});
