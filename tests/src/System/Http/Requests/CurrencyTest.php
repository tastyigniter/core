<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\CurrencyRequest;

it('has required rule for inputs', function() {
    $rules = (new CurrencyRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'currency_name'))
        ->and('required')->toBeIn(array_get($rules, 'currency_code'))
        ->and('required')->toBeIn(array_get($rules, 'country_id'))
        ->and('required')->toBeIn(array_get($rules, 'currency_status'));
});

it('has max characters rule for inputs', function() {
    $rules = (new CurrencyRequest)->rules();

    expect('between:2,32')->toBeIn(array_get($rules, 'currency_name'))
        ->and('size:3')->toBeIn(array_get($rules, 'currency_code'))
        ->and('size:1')->toBeIn(array_get($rules, 'symbol_position'))
        ->and('size:1')->toBeIn(array_get($rules, 'thousand_sign'))
        ->and('size:1')->toBeIn(array_get($rules, 'decimal_sign'))
        ->and('max:10')->toBeIn(array_get($rules, 'decimal_position'));
});
