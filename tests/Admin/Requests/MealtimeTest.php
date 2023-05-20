<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\MealtimeRequest;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new MealtimeRequest)->rules(), 'mealtime_name'))
        ->and('required')->toBeIn(array_get((new MealtimeRequest)->rules(), 'start_time'))
        ->and('required')->toBeIn(array_get((new MealtimeRequest)->rules(), 'end_time'))
        ->and('required')->toBeIn(array_get((new MealtimeRequest)->rules(), 'mealtime_status'));
});

it('has max characters rule for mealtime_name input', function () {
    expect('between:2,255')->toBeIn(array_get((new MealtimeRequest)->rules(), 'mealtime_name'));
});

it('has valid_time rule for start_time and end_time input', function () {
    expect('valid_time')->toBeIn(array_get((new MealtimeRequest)->rules(), 'start_time'))
        ->and('valid_time')->toBeIn(array_get((new MealtimeRequest)->rules(), 'end_time'));
});
