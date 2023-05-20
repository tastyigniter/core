<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\MenuRequest;

it('has rules for menu_name', function () {
    expect('required')->toBeIn(array_get((new MenuRequest)->rules(), 'menu_name'))
        ->and('between:2,255')->toBeIn(array_get((new MenuRequest)->rules(), 'menu_name'));
});

it('has rules for menu_price', function () {
    expect('required')->toBeIn(array_get((new MenuRequest)->rules(), 'menu_price'))
        ->and('min:0')->toBeIn(array_get((new MenuRequest)->rules(), 'menu_price'));
});

it('has rules for menu_description', function () {
    expect('between:2,1028')->toBeIn(array_get((new MenuRequest)->rules(), 'menu_description'));
});
