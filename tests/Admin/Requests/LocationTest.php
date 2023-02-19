<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Location;

it('has required rule for location_name, location_email and ...', function () {
    expect('required')->toBeIn(array_get((new Location)->rules(), 'location_name'));

    expect('required')->toBeIn(array_get((new Location)->rules(), 'location_email'));

    expect('required')->toBeIn(array_get((new Location)->rules(), 'location_address_1'));

    expect('required')->toBeIn(array_get((new Location)->rules(), 'location_country_id'));

    expect('required')->toBeIn(array_get((new Location)->rules(), 'options.auto_lat_lng'));
});

it('has sometimes rule for inputs', function () {
    expect('sometimes')->toBeIn(array_get((new Location)->rules(), 'location_telephone'));

    expect('sometimes')->toBeIn(array_get((new Location)->rules(), 'location_lat'));

    expect('sometimes')->toBeIn(array_get((new Location)->rules(), 'location_lng'));
});

it('has max characters rule for inputs', function () {
    expect('max:96')->toBeIn(array_get((new Location)->rules(), 'location_email'));

    expect('between:2,128')->toBeIn(array_get((new Location)->rules(), 'location_address_1'));

    expect('max:128')->toBeIn(array_get((new Location)->rules(), 'location_address_2'));

    expect('max:128')->toBeIn(array_get((new Location)->rules(), 'location_city'));

    expect('max:128')->toBeIn(array_get((new Location)->rules(), 'location_state'));

    expect('max:15')->toBeIn(array_get((new Location)->rules(), 'location_postcode'));

    expect('max:3028')->toBeIn(array_get((new Location)->rules(), 'description'));

    expect('max:255')->toBeIn(array_get((new Location)->rules(), 'permalink_slug'));

    expect('max:128')->toBeIn(array_get((new Location)->rules(), 'options.gallery.title'));

    expect('max:255')->toBeIn(array_get((new Location)->rules(), 'options.gallery.description'));
});
