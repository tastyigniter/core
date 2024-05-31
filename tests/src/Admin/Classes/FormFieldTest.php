<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\FormField;
use Tests\Admin\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->formField = new FormField('testField', 'Test Field');
});

it('can get name', function() {
    expect($this->formField->getName())->toBe('testField');
});

it('can get id', function() {
    expect($this->formField->getId())->toBe('field-testfield');
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
    ]);

    expect($this->formField->commentHtml)->toBeTrue()
        ->and($this->formField->placeholder)->toBe('Enter text')
        ->and($this->formField->dependsOn)->toBe(['otherField'])
        ->and($this->formField->required)->toBeTrue()
        ->and($this->formField->disabled)->toBeTrue()
        ->and($this->formField->cssClass)->toBe('test-class')
        ->and($this->formField->stretch)->toBeTrue()
        ->and($this->formField->context)->toBe('create')
        ->and($this->formField->hidden)->toBeTrue()
        ->and($this->formField->path)->toBe('/path/to/partial')
        ->and($this->formField->options)->toBeArray()
        ->and($this->formField->span)->toBe('left')
        ->and($this->formField->size)->toBe('large')
        ->and($this->formField->tab)->toBe('testTab')
        ->and($this->formField->commentAbove)->toBe('Test above comment')
        ->and($this->formField->comment)->toBe('Test comment')
        ->and($this->formField->defaults)->toBe('value')
        ->and($this->formField->defaultFrom)->toBe('otherField')
        ->and($this->formField->getAttributes())->toContain('class="test-class"');
});

it('can get value from data', function() {
    $data = ['testField' => 'test-value'];
    expect($this->formField->getValueFromData($data))->toBe('test-value');
});

it('can get default from data', function() {
    $data = ['defaultField' => 'default-value'];
    $this->formField->displayAs('text', ['defaultFrom' => 'defaultField']);
    expect($this->formField->getDefaultFromData($data))->toBe('default-value');
});

it('can get attributes', function() {
    $this->formField->displayAs('text', ['attributes' => ['class' => 'test-class']]);
    expect($this->formField->getAttributes())->toBe(' class="test-class"');
});

it('can get attributes with trigger', function() {
    $this->formField->displayAs('text', [
        'trigger' => [
            'action' => 'hide',
            'field' => 'otherField',
            'condition' => 'value',
        ],
        'attributes' => ['class' => 'test-class'],
    ]);

    $attributes = $this->formField->getAttributes('container', false);

    expect($attributes['data-trigger'])->toBe("[name='otherField']")
        ->and($attributes['data-trigger-action'])->toBe('hide')
        ->and($attributes['data-trigger-condition'])->toBe('value')
        ->and($attributes['data-trigger-closest-parent'])->toBe('form');
});

it('can get attributes with preset', function() {
    $this->formField->displayAs('text', [
        'preset' => [
            'field' => 'otherField',
            'type' => 'slug',
        ],
        'attributes' => ['class' => 'test-class'],
    ]);

    $attributes = $this->formField->getAttributes('field', false);

    expect($attributes['data-input-preset'])->toBe('[name="otherField"]')
        ->and($attributes['data-input-preset-type'])->toBe('slug')
        ->and($attributes['data-input-preset-closest-parent'])->toBe('form')
        ->and($attributes['class'])->toBe('test-class');
});

it('can resolve model attribute', function() {
    $model = new TestModel;
    $model->testField = 'test-value';
    [$resolvedModel, $attribute] = $this->formField->resolveModelAttribute($model);
    expect($resolvedModel->testField)->toBe('test-value')
        ->and($attribute)->toBe('testField');
});
