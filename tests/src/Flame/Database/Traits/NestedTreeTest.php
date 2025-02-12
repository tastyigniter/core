<?php

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\Cart\Models\Category;

it('creates instance with children', function() {
    $attributes = [
        'name' => 'Parent',
        'children' => [
            ['name' => 'Child'],
        ],
    ];
    $category = Category::create($attributes);

    expect($category->children->count())->toBe(1)
        ->and($category->name)->toBe('Parent')
        ->and($category->children->first()->name)->toBe('Child');
});

it('creates instance without children', function() {
    $attributes = ['name' => 'Parent'];
    $category = Category::create($attributes);

    expect($category->children->count())->toBe(0)
        ->and($category->name)->toBe('Parent');
});

it('fixes broken tree quietly', function() {
    $category = Category::factory()->create(['name' => 'Parent']);

    expect($category->fixBrokenTreeQuietly())->toBeNull();
});
