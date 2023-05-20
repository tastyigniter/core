<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\ReservationRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'location_id'))
        ->and('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'first_name'))
        ->and('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'last_name'))
        ->and('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'reserve_date'))
        ->and('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'reserve_time'))
        ->and('required')->toBeIn(array_get((new ReservationRequest)->rules(), 'guest_num'));
});

it('has max characters rule for inputs', function () {
    expect('between:1,48')->toBeIn(array_get((new ReservationRequest)->rules(), 'first_name'))
        ->and('between:1,48')->toBeIn(array_get((new ReservationRequest)->rules(), 'last_name'))
        ->and('max:96')->toBeIn(array_get((new ReservationRequest)->rules(), 'email'));
});

it('has valid_date and valid_time rule for inputs', function () {
    expect('valid_date')->toBeIn(array_get((new ReservationRequest)->rules(), 'reserve_date'))
        ->and('valid_time')->toBeIn(array_get((new ReservationRequest)->rules(), 'reserve_time'));
});
