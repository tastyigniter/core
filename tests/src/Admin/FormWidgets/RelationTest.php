<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Relation;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Models\Currency;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Models\TestStatusModel;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Relation');
    $this->formField->valueFrom = 'status_history';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
        'select' => 'comment',
        'order' => 'status_id asc',
    ]);
});

it('initialize correctly', function() {
    expect($this->relationWidget->relationFrom)->toBeNull()
        ->and($this->relationWidget->nameFrom)->toBe('name')
        ->and($this->relationWidget->sqlSelect)->toBe('comment')
        ->and($this->relationWidget->emptyOption)->toBeNull()
        ->and($this->relationWidget->scope)->toBeNull()
        ->and($this->relationWidget->order)->toBe('status_id asc');
});

it('renders correctly', function() {
    expect($this->relationWidget->render())->toBeString();
});

it('prepares vars correctly', function() {
    $this->relationWidget->prepareVars();

    expect($this->relationWidget->vars)->toHaveKey('field');
});

it('prepares vars correctly when relation type is belongsTo', function() {
    $this->formField = new FormField('status', 'Relation');
    $this->formField->valueFrom = 'status';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => StatusHistory::factory()->create(),
        'scope' => 'isForOrder',
    ]);

    $this->relationWidget->prepareVars();

    expect($this->relationWidget->vars)->toHaveKey('field');
});

it('applies custom sorted scope correctly', function() {
    $this->formField = new FormField('country', 'Relation');
    $this->formField->valueFrom = 'country';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => Currency::factory()->create(),
    ]);

    $this->relationWidget->prepareVars();

    expect($this->relationWidget->vars['field']->options)->not->toBeEmpty(1);
});

it('prepares vars correctly when model and related model are the same', function() {
    $this->formField = new FormField('status', 'Relation');
    $this->formField->valueFrom = 'status';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => (new TestStatusModel)->create([
            'status_name' => 'Test Status',
            'status_for' => 'order',
            'status_color' => '#000000',
            'status_comment' => 'Test Status',
            'notify_customer' => true,
        ]),
    ]);

    $this->relationWidget->prepareVars();

    expect($this->relationWidget->vars)->toHaveKey('field');
});

it('throws exception when relationship does not exists', function() {
    $this->formField = new FormField('invalid_relation', 'Relation');
    $this->formField->valueFrom = 'invalid_relation';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => StatusHistory::factory()->create(),
    ]);

    expect(fn() => $this->relationWidget->prepareVars())
        ->toThrow(SystemException::class, sprintf(
            lang('igniter::admin.alert_missing_model_definition'),
            $this->relationWidget->model::class, 'invalid_relation',
        ));
});

it('getSaveValue method works correctly', function($value, $expected) {
    $result = $this->relationWidget->getSaveValue($value);

    expect($expected)->toBe($result);
})->with([
    [null, null],
    ['', null],
    [[], null],
    ['value', 'value'],
    [[1, 2, 3], [1, 2, 3]],
]);

it('getSaveValue method returns NO_SAVE_DATA when field is disabled or hidden', function() {
    $this->formField->disabled = true;
    $this->formField->hidden = true;
    expect($this->relationWidget->getSaveValue(null))->toBe(FormField::NO_SAVE_DATA);
});

it('resolveModelAttribute method works correctly', function($attribute, $expected) {
    [$model, $attribute] = $this->relationWidget->resolveModelAttribute($attribute);

    expect($model)->toBeInstanceOf(Status::class)
        ->and($attribute)->toBe($expected);
})->with([
    ['attribute', 'attribute'],
    ['relation_field', 'relation_field'],
]);
