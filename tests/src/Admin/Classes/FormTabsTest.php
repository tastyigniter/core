<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Classes\FormTabs;

beforeEach(function() {
    $this->formField = new FormField('testField', 'Test Field');
});

it('constructs correctly', function() {
    $formTabs = new FormTabs();

    expect($formTabs->suppressTabs)->toBeTrue();
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

it('adds and removes field correctly', function() {
    $formTabs = new FormTabs();

    $formTabs->addField('testField', $this->formField, 'Test Tab');
    expect($formTabs->hasFields())->toBeTrue()
        ->and($formTabs->getFields())->toHaveKey('Test Tab');

    $formTabs->removeField('testField');
    expect($formTabs->hasFields())->toBeFalse();
});

it('gets all fields correctly', function() {
    $formTabs = new FormTabs();

    $formTabs->addField('testField', $this->formField, 'Test Tab');
    expect($formTabs->getAllFields())->toHaveKey('testField');
});
