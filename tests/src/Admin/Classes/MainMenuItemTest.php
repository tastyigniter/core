<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\MainMenuItem;

beforeEach(function() {
    $this->mainMenuItem = new MainMenuItem('testItem', 'Test Item');
});

it('constructs correctly', function() {
    expect($this->mainMenuItem->itemName)->toBe('testItem')
        ->and($this->mainMenuItem->label)->toBe('Test Item');
});

it('makes correctly', function() {
    $item = MainMenuItem::make('testItem', 'link', ['icon' => 'test-icon']);
    expect($item->itemName)->toBe('testItem')
        ->and($item->type)->toBe('link')
        ->and($item->icon)->toBe('test-icon');
});

it('creates dropdown correctly', function() {
    $item = MainMenuItem::dropdown('testItem');
    expect($item->itemName)->toBe('testItem')
        ->and($item->type)->toBe('dropdown');
});

it('creates link correctly', function() {
    $item = MainMenuItem::link('testItem');
    expect($item->itemName)->toBe('testItem')
        ->and($item->type)->toBe('link');
});

it('creates partial correctly', function() {
    $item = MainMenuItem::partial('testItem', '/path/to/partial');
    expect($item->itemName)->toBe('testItem')
        ->and($item->type)->toBe('partial')
        ->and($item->path)->toBe('/path/to/partial');
});

it('creates widget correctly', function() {
    $item = MainMenuItem::widget('testItem', 'TestClass');
    expect($item->itemName)->toBe('testItem')
        ->and($item->type)->toBe('widget')
        ->and($item->config['widget'])->toBe('TestClass');
});

it('sets and gets options correctly', function() {
    expect($this->mainMenuItem->options())->toBe([]);
    $this->mainMenuItem->options(['option1', 'option2']);
    expect($this->mainMenuItem->options())->toBe(['option1', 'option2']);
});

it('sets and gets callable options correctly', function() {
    $this->mainMenuItem->options(function(): array {
        return ['option1', 'option2'];
    });
    expect($this->mainMenuItem->options())->toBe(['option1', 'option2']);
});

it('displays as correctly', function() {
    $this->mainMenuItem->displayAs('text', [
        'priority' => 99,
        'anchor' => 'testAnchor',
        'options' => ['option1', 'option2'],
        'context' => ['context1', 'context2'],
        'icon' => 'test-icon',
        'path' => '/path/to/partial',
        'cssClass' => 'test-class',
        'attributes' => ['class' => 'test-class'],
        'disabled' => true,
    ]);
    expect($this->mainMenuItem->type)->toBe('text')
        ->and($this->mainMenuItem->anchor)->toBe('testAnchor')
        ->and($this->mainMenuItem->options)->toBe(['option1', 'option2'])
        ->and($this->mainMenuItem->context)->toBe(['context1', 'context2'])
        ->and($this->mainMenuItem->icon)->toBe('test-icon')
        ->and($this->mainMenuItem->path)->toBe('/path/to/partial')
        ->and($this->mainMenuItem->cssClass)->toBe('test-class')
        ->and($this->mainMenuItem->attributes)->toBe(['class' => 'test-class'])
        ->and($this->mainMenuItem->disabled)->toBeTrue();
});

it('gets attributes correctly', function() {
    $this->mainMenuItem->disabled = true;
    $this->mainMenuItem->attributes([
        'class' => 'test-class',
        'href' => '/path/to/partial',
    ]);

    $attributes = $this->mainMenuItem->getAttributes(false);
    expect($attributes)->toBe([
        'class' => 'test-class',
        'href' => admin_url('/path/to/partial'),
        'disabled' => 'disabled',
    ]);
});

it('gets id correctly', function() {
    $this->mainMenuItem->idPrefix = 'prefix';
    expect($this->mainMenuItem->getId())->toBe('prefix-menuitem-testItem')
        ->and($this->mainMenuItem->getId('suffix'))->toBe('prefix-menuitem-testItem-suffix');
});

it('sets label correctly', function() {
    $this->mainMenuItem->label('NewLabel');
    expect($this->mainMenuItem->label)->toBe('NewLabel');
});

it('sets id prefix correctly', function() {
    $this->mainMenuItem->idPrefix('prefix');
    expect($this->mainMenuItem->idPrefix)->toBe('prefix');
});

it('sets anchor correctly', function() {
    $this->mainMenuItem->anchor('testAnchor');
    expect($this->mainMenuItem->anchor)->toBe('testAnchor');
});

it('sets disabled correctly', function() {
    $this->mainMenuItem->disabled();
    expect($this->mainMenuItem->disabled)->toBeTrue();
});

it('sets icon correctly', function() {
    $this->mainMenuItem->icon('test-icon');
    expect($this->mainMenuItem->icon)->toBe('test-icon');
});

it('sets attributes correctly', function() {
    $this->mainMenuItem->attributes(['class' => 'test-class']);
    expect($this->mainMenuItem->attributes)->toBe(['class' => 'test-class']);
});

it('sets path correctly', function() {
    $this->mainMenuItem->path('/path/to/partial');
    expect($this->mainMenuItem->path)->toBe('/path/to/partial');
});

it('sets priority correctly', function() {
    $this->mainMenuItem->priority(10);
    expect($this->mainMenuItem->priority)->toBe(10);
});

it('sets permission correctly', function() {
    $this->mainMenuItem->permission('testPermission');
    expect($this->mainMenuItem->permission)->toBe('testPermission');
});

it('sets config correctly', function() {
    $this->mainMenuItem->config(['icon' => 'test-icon']);
    expect($this->mainMenuItem->config)->toBe(['icon' => 'test-icon']);
});

it('merges config correctly', function() {
    $this->mainMenuItem->config(['icon' => 'test-icon']);
    $this->mainMenuItem->mergeConfig(['anchor' => 'testAnchor']);

    expect($this->mainMenuItem->config)->toBe(['icon' => 'test-icon', 'anchor' => 'testAnchor']);
});
