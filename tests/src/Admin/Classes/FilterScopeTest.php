<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\FilterScope;

it('constructs correctly', function() {
    $filterScope = new FilterScope('testScopeName', 'Test Scope');
    $filterScope->displayAs('select', [
        'options' => ['option1', 'option2'],
        'context' => ['context1', 'context2'],
        'default' => 'default',
        'conditions' => 'name = :name',
        'scope' => 'testScope',
        'cssClass' => 'css-class',
        'nameFrom' => 'nameColumn',
        'descriptionFrom' => 'descriptionColumn',
        'disabled' => true,
        'mode' => 'mode',
    ]);

    expect($filterScope->scopeName)->toBe('testScopeName')
        ->and($filterScope->label)->toBe('Test Scope')
        ->and($filterScope->idPrefix)->toBeNull()
        ->and($filterScope->nameFrom)->toBe('nameColumn')
        ->and($filterScope->descriptionFrom)->toBe('descriptionColumn')
        ->and($filterScope->value)->toBeNull()
        ->and($filterScope->type)->toBe('select')
        ->and($filterScope->options)->toBe(['option1', 'option2'])
        ->and($filterScope->context)->toBe(['context1', 'context2'])
        ->and($filterScope->disabled)->toBeTrue()
        ->and($filterScope->defaults)->toBe('default')
        ->and($filterScope->conditions)->toBe('name = :name')
        ->and($filterScope->scope)->toBe('testScope')
        ->and($filterScope->cssClass)->toBe('css-class')
        ->and($filterScope->mode)->toBe('mode')
        ->and($filterScope->minDate)->toBeNull()
        ->and($filterScope->maxDate)->toBeNull()
        ->and($filterScope->config)->toBeArray();
});

it('can get id with optional prefix and suffix', function($scopeName, ?string $suffix, $prefix, $expectedId) {
    $filterScope = new FilterScope($scopeName, 'Test Scope');
    $filterScope->displayAs('text', ['idPrefix' => $prefix]);

    expect($filterScope->getId($suffix))->toBe($expectedId);
})->with([
    ['testScope', null, null, 'scope-testScope'],
    ['testScope', 'suffix', null, 'scope-testScope-suffix'],
    ['testScope', null, 'prefix', 'prefix-scope-testScope'],
    ['testScope', 'suffix', 'prefix', 'prefix-scope-testScope-suffix'],
]);
