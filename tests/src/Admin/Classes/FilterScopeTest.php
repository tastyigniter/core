<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Classes\FilterScope;

dataset('ids', [
    ['testScope', null, null, 'scope-testScope'],
    ['testScope', 'suffix', null, 'scope-testScope-suffix'],
    ['testScope', null, 'prefix', 'prefix-scope-testScope'],
    ['testScope', 'suffix', 'prefix', 'prefix-scope-testScope-suffix'],
]);

it('can get id with optional prefix and suffix', function($scopeName, $suffix, $prefix, $expectedId) {
    $filterScope = new FilterScope($scopeName, 'Test Scope');
    $filterScope->displayAs('text', ['idPrefix' => $prefix]);

    expect($filterScope->getId($suffix))->toBe($expectedId);
})->with('ids');