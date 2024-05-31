<?php

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
    $this->mainMenuItem->options(['option1', 'option2']);
    expect($this->mainMenuItem->options())->toBe(['option1', 'option2']);
});

it('displays as correctly', function() {
    $this->mainMenuItem->displayAs('text', ['icon' => 'test-icon']);
    expect($this->mainMenuItem->type)->toBe('text')
        ->and($this->mainMenuItem->icon)->toBe('test-icon');
});

it('gets attributes correctly', function() {
    $this->mainMenuItem->attributes(['class' => 'test-class']);
    expect($this->mainMenuItem->getAttributes(false))->toBe(['class' => 'test-class']);
});

it('gets id correctly', function() {
    expect($this->mainMenuItem->getId())->toBe('menuitem-testItem')
        ->and($this->mainMenuItem->getId('suffix'))->toBe('menuitem-testItem-suffix');
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
