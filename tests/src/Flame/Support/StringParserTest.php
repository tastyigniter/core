<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Support\StringParser;

it('parses template', function($template, $data, $expected) {
    expect((new StringParser)->parse($template, $data))->toBe($expected);
})->with([
    'single variable' => ['Hello, {name}!', ['name' => 'John'], 'Hello, John!'],
    'string data' => ['Hello, {name}!', 'John', 'Hello, {name}!'],
    'multiple variables' => ['Hello, {name}! You are {age} years old.', ['name' => 'John', 'age' => 30], 'Hello, John! You are 30 years old.'],
    'nested arrays' => [
        '{user}{name} is {age} years old and has {cars}{name}, {/cars}{/user}', [
            'user' => [
                ['name' => 'John', 'age' => 30, 'cars' => [['name' => 'BMW'], ['name' => 'Audi']]],
                ['name' => 'Jane', 'age' => 25, 'cars' => [['name' => 'BMW'], ['name' => 'Audi']]],
            ],
        ],
        'John is 30 years old and has BMW, Audi, Jane is 25 years old and has BMW, Audi, '],
    'empty data' => ['Hello, {name}!', [], 'Hello, {name}!'],
    'non-scalar value' => ['Hello, {name}!', ['name' => (object)['name' => 'John']], 'Hello, !'],
    'scalar value' => ['Hello, {name}!', ['name' => ['John']], 'Hello, {name}!'],
]);
