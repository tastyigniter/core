<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserRoleRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new UserRoleRequest)->rules(), 'name'))
        ->and('required')->toBeIn(array_get((new UserRoleRequest)->rules(), 'permissions'))
        ->and('required')->toBeIn(array_get((new UserRoleRequest)->rules(), 'permissions.*'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,32')->toBeIn(array_get((new UserRoleRequest)->rules(), 'code'))
        ->and('between:2,255')->toBeIn(array_get((new UserRoleRequest)->rules(), 'name'));
});

it('has alpha_dash rule for inputs', function () {
    expect('alpha_dash')->toBeIn(array_get((new UserRoleRequest)->rules(), 'code'));
});

it('has unique:admin_user_roles rule for inputs', function () {
    expect('unique:admin_user_roles')->toBeIn(array_get((new UserRoleRequest)->rules(), 'name'));
});
