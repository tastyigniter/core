<?php

namespace Tests\Admin\Requests;

use Igniter\Main\Requests\CustomerRequest;

it('has rules for first_name field', function () {
    $rules = array_get((new CustomerRequest)->rules(), 'first_name');

    expect('required')->toBeIn($rules)
        ->and('between:1,48')->toBeIn($rules);
});

it('has rules for last_name field', function () {
    $rules = array_get((new CustomerRequest)->rules(), 'last_name');

    expect('required')->toBeIn($rules)
        ->and('between:1,48')->toBeIn($rules);
});

it('has rules for email field', function () {
    $rules = array_get((new CustomerRequest)->rules(), 'email');

    expect('email:filter')->toBeIn($rules)
        ->and('max:96')->toBeIn($rules)
        ->and('unique:customers,email')->toBeIn($rules);
});
