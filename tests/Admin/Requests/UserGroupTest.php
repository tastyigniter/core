<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserGroupRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new UserGroupRequest)->rules(), 'user_group_name'))
        ->and('required')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign'))
        ->and('required_if:auto_assign,true')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign_mode'))
        ->and('required_if:auto_assign_mode,2')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign_limit'))
        ->and('required_if:auto_assign,true')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign_availability'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,255')->toBeIn(array_get((new UserGroupRequest)->rules(), 'user_group_name'))
        ->and('max:2')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign_mode'))
        ->and('max:99')->toBeIn(array_get((new UserGroupRequest)->rules(), 'auto_assign_limit'));
});
