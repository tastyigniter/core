<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Database\Model;
use Igniter\Tests\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->formField = new FormField('testField', 'Test Field');
});

it('can get name', function() {
    expect($this->formField->getName())->toBe('testField')
        ->and($this->formField->getName('arrayName'))->toBe('arrayName[testField]');
});

it('can get id', function() {
    $this->formField->arrayName = 'arrayName';

    expect($this->formField->getId())->toBe('field-arrayname-testfield')
        ->and($this->formField->getId('suffix'))->toBe('field-arrayname-testfield-suffix');
});

it('can get id with prefix', function() {
    $this->formField->arrayName = 'arrayName';
    $this->formField->idPrefix = 'idPrefix';

    expect($this->formField->getId())->toBe('idprefix-field-arrayname-testfield');
});

it('evaluates config correctly', function() {
    $this->formField->displayAs('text', [
        'commentHtml' => true,
        'placeholder' => 'Enter text',
        'dependsOn' => ['otherField'],
        'required' => true,
        'disabled' => true,
        'cssClass' => 'test-class',
        'stretch' => true,
        'context' => 'create',
        'hidden' => true,
        'path' => '/path/to/partial',
        'options' => [],
        'span' => 'left',
        'size' => 'large',
        'tab' => 'testTab',
        'commentAbove' => 'Test above comment',
        'comment' => 'Test comment',
        'default' => 'value',
        'defaultFrom' => 'otherField',
        'attributes' => ['class' => 'test-class'],
        'containerAttributes' => ['class' => 'test-class'],
        'valueFrom' => 'otherField',
        'extraConfig' => 'extra',
    ]);

    expect($this->formField->getConfig('extraConfig'))->toBe('extra')
        ->and($this->formField->commentHtml)->toBeTrue()
        ->and($this->formField->placeholder)->toBe('Enter text')
        ->and($this->formField->dependsOn)->toBe(['otherField'])
        ->and($this->formField->required)->toBeTrue()
        ->and($this->formField->disabled)->toBeTrue()
        ->and($this->formField->cssClass)->toBe('test-class')
        ->and($this->formField->stretch)->toBeTrue()
        ->and($this->formField->context)->toBe('create')
        ->and($this->formField->hidden)->toBeTrue()
        ->and($this->formField->path)->toBe('/path/to/partial')
        ->and($this->formField->options())->toBeArray()
        ->and($this->formField->span)->toBe('left')
        ->and($this->formField->size)->toBe('large')
        ->and($this->formField->tab)->toBe('testTab')
        ->and($this->formField->commentAbove)->toBe('Test above comment')
        ->and($this->formField->comment)->toBe('Test comment')
        ->and($this->formField->defaults)->toBe('value')
        ->and($this->formField->defaultFrom)->toBe('otherField')
        ->and($this->formField->hasAttribute('class'))->toBeTrue()
        ->and($this->formField->hasAttribute('class', 'container'))->toBeTrue()
        ->and($this->formField->getAttributes())->toContain('class="test-class"')
        ->and($this->formField->getAttributes('container'))->toContain('class="test-class"');
});

it('can get value from object data', function() {
    $dataObject = (object)['testField' => 'test-value'];

    expect($this->formField->getValueFromData((object)[]))->toBeNull()
        ->and($this->formField->getValueFromData($dataObject))->toBe('test-value');
});

it('can get value from array data', function() {
    $data = ['testField' => 'test-value'];

    expect($this->formField->getValueFromData([]))->toBeNull()
        ->and($this->formField->getValueFromData($data))->toBe('test-value');
});

it('can get value from model relation data', function() {
    $model = new class extends Model
    {
        public $relation = [
            'belongsTo' => ['testRelation' => [TestModel::class]],
        ];
    };

    $relation = new TestModel;
    $relation->testField = 'test-value';
    $model->setRelation('testRelation', $relation);
    $this->formField->fieldName = 'testRelation[testField]';
    expect($this->formField->getValueFromData($model))->toBe('test-value');

    $this->formField->fieldName = 'testRelation';
    $relation->testRelation = 'test-value';
    expect($this->formField->getValueFromData($model))->toBeInstanceOf(TestModel::class);
});

it('can get callable options', function() {
    expect($this->formField->options())->toBe([]);

    $this->formField->displayAs('select', [
        'options' => function(): array {
            return ['option1', 'option2'];
        },
    ]);

    expect($this->formField->options())->toBe(['option1', 'option2']);
});

it('can get default from data', function() {
    $data = ['defaultField' => 'default-value'];
    $this->formField->displayAs('text', ['defaultFrom' => 'defaultField']);
    expect($this->formField->getDefaultFromData($data))->toBe('default-value');
});

it('can get default from data if no defaultField', function() {
    expect($this->formField->getDefaultFromData([]))->toBeNull();

    $this->formField->defaults = 'default-value';
    expect($this->formField->getDefaultFromData([]))->toBe('default-value');
});

it('can get attributes', function() {
    $this->formField->displayAs('switch', [
        'attributes' => ['class' => 'test-class'],
        'readOnly' => true,
        'disabled' => true,
    ]);

    $attributes = $this->formField->getAttributes();

    expect($attributes)->toContain(' class="test-class"')
        ->and($attributes)->toContain(' readonly="readonly"')
        ->and($attributes)->toContain(' disabled="disabled"')
        ->and($attributes)->toContain(' onclick="return false;"')
        ->and($this->formField->hasAttribute('invalid-attribute'))->toBeFalse()
        ->and($this->formField->hasAttribute('attribute', 'invalid-position'))->toBeFalse();
});

it('can get trigger attributes when action is hide and position is field', function() {
    $this->formField->displayAs('text', [
        'trigger' => [
            'action' => 'hide',
            'field' => 'otherField',
            'condition' => 'value',
        ],
        'attributes' => ['field' => ['data-trigger' => "[name='otherField']"]],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-trigger'])->toBe("[name='otherField']")
        ->and($attributes)->not->toHaveKey('data-trigger-action');
});

it('can get trigger attributes when action is enable and position is container', function() {
    $this->formField->displayAs('text', [
        'trigger' => [
            'action' => 'enable',
            'field' => 'otherField',
            'condition' => 'value',
        ],
    ]);

    $attributes = $this->formField->getAttributes('container', false);

    expect($attributes)->not->toHaveKey('data-trigger-action');
});

it('can get trigger attributes when action is checked', function() {
    $this->formField->displayAs('text', [
        'trigger' => [
            'action' => 'checked',
            'field' => 'otherField',
            'condition' => 'value',
        ],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-trigger'])->toBe("[name='otherField']")
        ->and($attributes['data-trigger-action'])->toBe('checked')
        ->and($attributes['data-trigger-condition'])->toBe('value')
        ->and($attributes['data-trigger-closest-parent'])->toBe('form');
});

it('can get trigger attributes when action is checked and is array field', function() {
    $this->formField->arrayName = 'arrayName';
    $this->formField->displayAs('text', [
        'trigger' => [
            'action' => 'checked',
            'field' => 'otherField',
            'condition' => 'value',
        ],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-trigger'])->toBe("[name='arrayName[otherField]']")
        ->and($attributes['data-trigger-action'])->toBe('checked')
        ->and($attributes['data-trigger-condition'])->toBe('value')
        ->and($attributes['data-trigger-closest-parent'])->toBe('form');
});

it('can get preset attributes when preset is string', function() {
    $this->formField->displayAs('text', [
        'preset' => 'otherField',
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-input-preset'])->toBe('[name="otherField"]')
        ->and($attributes['data-input-preset-type'])->toBe('slug')
        ->and($attributes['data-input-preset-closest-parent'])->toBe('form');
});

it('can get preset attributes with array field', function() {
    $this->formField->arrayName = 'arrayName';
    $this->formField->displayAs('text', [
        'preset' => [
            'field' => 'otherField',
            'type' => 'slug',
            'prefixInput' => 'prefixField',
        ],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-input-preset'])->toBe('[name="arrayName[otherField]"]')
        ->and($attributes['data-input-preset-type'])->toBe('slug')
        ->and($attributes['data-input-preset-closest-parent'])->toBe('form')
        ->and($attributes['data-input-preset-prefix-input'])->toBe('prefixField');
});

it('can get preset attributes correctly', function() {
    $this->formField->displayAs('text', [
        'preset' => [
            'field' => 'otherField',
            'type' => 'slug',
        ],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-input-preset'])->toBe('[name="otherField"]')
        ->and($attributes['data-input-preset-type'])->toBe('slug')
        ->and($attributes['data-input-preset-closest-parent'])->toBe('form');
});

it('can resolve model attribute', function() {
    $model = new TestModel;
    $model->testField = 'test-value';
    [$resolvedModel, $attribute] = $this->formField->resolveModelAttribute($model);
    expect($resolvedModel->testField)->toBe('test-value')
        ->and($attribute)->toBe('testField');
});

it('can resolve model nested attribute', function() {
    $model = new TestModel;
    $model->testRelation = (object)[
        'testField' => 'test-value',
    ];
    [$resolvedModel, $attribute] = $this->formField->resolveModelAttribute($model, 'testRelation[testField]');
    expect($resolvedModel->testField)->toBe('test-value')
        ->and($attribute)->toBe('testField');
});
