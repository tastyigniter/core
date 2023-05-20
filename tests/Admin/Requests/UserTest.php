<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new UserRequest)->rules(), 'name'))
        ->and('required')->toBeIn(array_get((new UserRequest)->rules(), 'email'))
        ->and('required')->toBeIn(array_get((new UserRequest)->rules(), 'username'))
        ->and('required_if:send_invite,0')->toBeIn(array_get((new UserRequest)->rules(), 'password'))
        ->and('required')->toBeIn(array_get((new UserRequest)->rules(), 'user_role_id'))
        ->and('required')->toBeIn(array_get((new UserRequest)->rules(), 'groups'));
});

it('has sometimes rule for inputs', function () {
    expect('sometimes')->toBeIn(array_get((new UserRequest)->rules(), 'password'))
        ->and('sometimes')->toBeIn(array_get((new UserRequest)->rules(), 'user_role_id'))
        ->and('sometimes')->toBeIn(array_get((new UserRequest)->rules(), 'groups'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,255')->toBeIn(array_get((new UserRequest)->rules(), 'name'))
        ->and('max:96')->toBeIn(array_get((new UserRequest)->rules(), 'email'))
        ->and('email:filter')->toBeIn(array_get((new UserRequest)->rules(), 'email'))
        ->and('between:2,32')->toBeIn(array_get((new UserRequest)->rules(), 'username'))
        ->and('between:6,32')->toBeIn(array_get((new UserRequest)->rules(), 'password'));
});

it('has unique rule for inputs', function () {
    expect('unique:admin_users,email')->toBeIn(array_get((new UserRequest)->rules(), 'email'))
        ->and('unique:admin_users,username')->toBeIn(array_get((new UserRequest)->rules(), 'username'));
});
