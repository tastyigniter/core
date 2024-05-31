<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\CountryRequest;

it('has required rule for inputs', function() {
    $rules = (new CountryRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'country_name'))
        ->and('required')->toBeIn(array_get($rules, 'priority'))
        ->and('required')->toBeIn(array_get($rules, 'iso_code_2'))
        ->and('required')->toBeIn(array_get($rules, 'iso_code_3'))
        ->and('required')->toBeIn(array_get($rules, 'status'));
});

it('has max characters rule for inputs', function() {
    $rules = (new CountryRequest)->rules();

    expect('between:2,255')->toBeIn(array_get($rules, 'country_name'))
        ->and('size:2')->toBeIn(array_get($rules, 'iso_code_2'))
        ->and('size:3')->toBeIn(array_get($rules, 'iso_code_3'))
        ->and('min:2')->toBeIn(array_get($rules, 'format'));
});
