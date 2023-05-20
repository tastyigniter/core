<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\TableRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new TableRequest)->rules(), 'table_name'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'min_capacity'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'max_capacity'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'extra_capacity'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'priority'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'is_joinable'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'table_status'))
        ->and('required')->toBeIn(array_get((new TableRequest)->rules(), 'locations'));
});

it('has rules for table_name input', function () {
    expect('between:2,255')->toBeIn(array_get((new TableRequest)->rules(), 'table_name'));
});

it('has min character rule for min_capacity and max_capacity input', function () {
    expect('min:1')->toBeIn(array_get((new TableRequest)->rules(), 'min_capacity'))
        ->and('min:1')->toBeIn(array_get((new TableRequest)->rules(), 'max_capacity'));
});

it('has rules for max_capacity input', function () {
    expect('lte:max_capacity')->toBeIn(array_get((new TableRequest)->rules(), 'min_capacity'))
        ->and('gte:min_capacity')->toBeIn(array_get((new TableRequest)->rules(), 'max_capacity'));
});
