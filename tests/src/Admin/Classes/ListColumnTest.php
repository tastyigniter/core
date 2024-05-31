<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\ListColumn;

beforeEach(function() {
    $this->listColumn = new ListColumn('testColumn', 'Test Column');
});

it('constructs correctly', function() {
    expect($this->listColumn->columnName)->toBe('testColumn')
        ->and($this->listColumn->label)->toBe('Test Column');
});

it('evaluates config correctly', function() {
    $this->listColumn->displayAs('text', [
        'width' => '10%',
        'cssClass' => 'test-class',
        'searchable' => true,
        'sortable' => false,
        'editable' => true,
        'invisible' => true,
        'valueFrom' => 'testValue',
        'default' => 'defaultValue',
        'select' => 'testSelect',
        'relation' => 'testRelation',
        'attributes' => ['class' => 'test-attribute'],
        'format' => 'testFormat',
        'path' => '/path/to/partial',
        'formatter' => fn() => 'testFormatter',
        'iconCssClass' => 'test-icon-class',
    ]);

    expect($this->listColumn->type)->toBe('text')
        ->and($this->listColumn->width)->toBe('10%')
        ->and($this->listColumn->cssClass)->toBe('test-class')
        ->and($this->listColumn->searchable)->toBeTrue()
        ->and($this->listColumn->sortable)->toBeFalse()
        ->and($this->listColumn->editable)->toBeTrue()
        ->and($this->listColumn->invisible)->toBeTrue()
        ->and($this->listColumn->valueFrom)->toBe('testValue')
        ->and($this->listColumn->defaults)->toBe('defaultValue')
        ->and($this->listColumn->sqlSelect)->toBe('testSelect')
        ->and($this->listColumn->relation)->toBe('testRelation')
        ->and($this->listColumn->attributes)->toBe(['class' => 'test-attribute'])
        ->and($this->listColumn->format)->toBe('testFormat')
        ->and($this->listColumn->path)->toBe('/path/to/partial')
        ->and($this->listColumn->formatter)->toBeCallable()
        ->and($this->listColumn->iconCssClass)->toBe('test-icon-class');
});

it('gets name correctly', function() {
    expect($this->listColumn->getName())->toBe('testColumn');
});

it('gets id correctly', function() {
    expect($this->listColumn->getId())->toBe('column-testColumn')
        ->and($this->listColumn->getId('suffix'))->toBe('column-testColumn-suffix');
});
