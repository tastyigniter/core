<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\CategoryRequest;

it('has rules for name input', function () {
    $rules = array_get((new CategoryRequest)->rules(), 'name');

    expect('required')->toBeIn($rules)
        ->and('between:2,255')->toBeIn($rules);
});

it('has rules for permalink slug input', function () {
    $rules = array_get((new CategoryRequest)->rules(), 'permalink_slug');

    expect('alpha_dash')->toBeIn($rules)
        ->and('max:255')->toBeIn($rules);
});
