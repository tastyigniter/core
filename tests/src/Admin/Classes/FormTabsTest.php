<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Classes\FormTabs;

beforeEach(function() {
    $this->formField = new FormField('testField', 'Test Field');
});

it('constructs correctly', function() {
    $formTabs = new FormTabs;

    expect($formTabs->fields)->toBe([])
        ->and($formTabs->defaultTab)->toBe('igniter::admin.form.undefined_tab')
        ->and($formTabs->stretch)->toBeNull()
        ->and($formTabs->suppressTabs)->toBeTrue()
        ->and($formTabs->section)->toBe('outside')
        ->and($formTabs->cssClass)->toBeNull()
        ->and($formTabs->getIterator())->toBeIterable()
        ->and($formTabs->hasFields())->toBeFalse()
        ->and($formTabs->offsetSet('newField', $this->formField))->toBeNull()
        ->and($formTabs->offsetExists('newField'))->toBeTrue()
        ->and($formTabs->offsetUnset('newField'))->toBeNull()
        ->and($formTabs->offsetGet('newField'))->toBeNull();
});

it('evaluates config correctly', function() {
    $config = [
        'defaultTab' => 'Default Tab',
        'stretch' => true,
        'suppressTabs' => true,
        'cssClass' => 'test-class',
    ];

    $formTabs = new FormTabs('outside', $config);

    expect($formTabs->defaultTab)->toBe('Default Tab')
        ->and($formTabs->stretch)->toBeTrue()
        ->and($formTabs->suppressTabs)->toBeTrue()
        ->and($formTabs->cssClass)->toBe('test-class');
});

it('adds fields to default tab', function() {
    $formTabs = new FormTabs;

    $formTabs->addField('testField', $this->formField);

    expect($formTabs->fields['igniter::admin.form.undefined_tab']['testField'])->toBe($this->formField);
});

it('adds fields to primary tab', function() {
    $formTabs = new FormTabs;

    $formTabs->addField('testField', $this->formField, 'primary');

    expect($formTabs->fields['primary']['testField'])->toBe($this->formField);
});

it('adds and removes field correctly', function() {
    $formTabs = new FormTabs;

    $formTabs->addField('testField', $this->formField, 'Test Tab');

    expect($formTabs->removeField('testField'))->toBeTrue()
        ->and($formTabs->removeField('invalidTest'))->toBeFalse();

});

it('gets all fields correctly', function() {
    $formTabs = new FormTabs;

    $formTabs->addField('testField', $this->formField, 'Test Tab');

    expect($formTabs->getAllFields())->toHaveKey('testField');
});
