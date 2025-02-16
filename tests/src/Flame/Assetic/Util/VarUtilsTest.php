<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Util;

use Igniter\Flame\Assetic\Util\VarUtils;

it('resolves template with all variables', function() {
    $template = 'Hello, {name}!';
    $vars = ['name'];
    $values = ['name' => 'World'];

    expect(VarUtils::resolve($template, $vars, $values))->toBe('Hello, World!');
});

it('throws exception for missing variable value', function() {
    $template = 'Hello, {name}!';
    $vars = ['name'];
    $values = [];

    expect(fn() => VarUtils::resolve($template, $vars, $values))->toThrow(\InvalidArgumentException::class);
});

it('ignores unused variables in template', function() {
    $template = 'Hello, World!';
    $vars = ['name'];
    $values = ['name' => 'World'];

    expect(VarUtils::resolve($template, $vars, $values))->toBe('Hello, World!');
});

it('returns all combinations of variables', function() {
    $vars = ['color', 'size'];
    $values = [
        'color' => ['red', 'blue'],
        'size' => ['small', 'large'],
    ];

    expect(VarUtils::getCombinations($vars, $values))->toBe([
        ['color' => 'red', 'size' => 'small'],
        ['color' => 'blue', 'size' => 'small'],
        ['color' => 'red', 'size' => 'large'],
        ['color' => 'blue', 'size' => 'large'],
    ]);
});

it('returns empty combination for no variables', function() {
    $vars = [];
    $values = [];

    expect(VarUtils::getCombinations($vars, $values))->toBe([[]]);
});

it('ignores values for unused variables', function() {
    $vars = ['color'];
    $values = [
        'color' => ['red', 'blue'],
        'size' => ['small', 'large'],
    ];

    expect(VarUtils::getCombinations($vars, $values))->toBe([
        ['color' => 'red'],
        ['color' => 'blue'],
    ]);
});
