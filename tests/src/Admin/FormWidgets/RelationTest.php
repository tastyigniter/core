<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Relation;
use Igniter\Admin\Models\Status;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;
use Illuminate\View\Factory;

dataset('initialization', [
    ['relationFrom', null],
    ['nameFrom', 'name'],
    ['sqlSelect', null],
    ['emptyOption', null],
    ['scope', null],
    ['order', null],
]);

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('status_history', 'Relation');
    $this->formField->valueFrom = 'status_history';
    $this->relationWidget = new Relation($this->controller, $this->formField, [
        'model' => Status::factory()->create(),
    ]);
});

it('initialize correctly', function($property, $expected) {
    expect($this->relationWidget->$property)->toBe($expected);
})->with('initialization');

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('relation/relation'));

    $this->relationWidget->render();
})->throws(\Exception::class);

it('prepares vars correctly', function() {
    $this->relationWidget->prepareVars();

    expect($this->relationWidget->vars)->toHaveKey('field');
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
