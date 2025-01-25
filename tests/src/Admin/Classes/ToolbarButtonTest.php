<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\ToolbarButton;

beforeEach(function() {
    $this->toolbarButton = new ToolbarButton('test');
});

it('tests displayAs', function() {
    $this->toolbarButton->displayAs('text', ['context' => 'test', 'permission' => 'test', 'label' => 'Test Label', 'class' => 'test-class']);

    expect($this->toolbarButton->type)->toBe('text')
        ->and($this->toolbarButton->context)->toBe('test')
        ->and($this->toolbarButton->permission)->toBe('test')
        ->and($this->toolbarButton->label)->toBe('Test Label')
        ->and($this->toolbarButton->cssClass)->toBe('test-class');
});

it('tests getAttributes', function() {
    $this->toolbarButton->displayAs('text', [
        'context' => 'test',
        'permission' => 'test',
        'label' => 'Test Label',
        'class' => 'test-class',
        'href' => 'test',
        'arrayAttribute' => ['test'],
        'disabled' => true,
    ]);

    $attributes = $this->toolbarButton->getAttributes();

    expect($attributes)->toContain('href="'.admin_url('test').'"')
        ->and($attributes)->toContain('class="test-class"');
});

it('tests menuItems', function() {
    $this->toolbarButton->menuItems(['item1', 'item2']);

    $menuItems = $this->toolbarButton->menuItems();

    expect($menuItems)->toBe(['item1', 'item2']);
});
