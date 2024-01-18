<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\StatusRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new StatusRequest)->rules(), 'status_name'))
        ->and('required')->toBeIn(array_get((new StatusRequest)->rules(), 'status_for'))
        ->and('required')->toBeIn(array_get((new StatusRequest)->rules(), 'notify_customer'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,32')->toBeIn(array_get((new StatusRequest)->rules(), 'status_name'))
        ->and('max:7')->toBeIn(array_get((new StatusRequest)->rules(), 'status_color'))
        ->and('max:1028')->toBeIn(array_get((new StatusRequest)->rules(), 'status_comment'));
});

it('has in:order,reservation rule for inputs', function () {
    expect('in:order,reservation')->toBeIn(array_get((new StatusRequest)->rules(), 'status_for'));
});
