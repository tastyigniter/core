<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\GeneralSettingsRequest;

it('returns correct attribute labels', function() {
    $attributes = (new GeneralSettingsRequest)->attributes();

    expect($attributes)->toHaveKey('site_name', lang('igniter::system.settings.label_site_name'))
        ->and($attributes)->toHaveKey('site_email', lang('igniter::system.settings.label_site_email'))
        ->and($attributes)->toHaveKey('site_logo', lang('igniter::system.settings.label_site_logo'))
        ->and($attributes)->toHaveKey('maps_api_key', lang('igniter::system.settings.label_maps_api_key'))
        ->and($attributes)->toHaveKey('distance_unit', lang('igniter::system.settings.label_distance_unit'))
        ->and($attributes)->toHaveKey('timezone', lang('igniter::system.settings.label_timezone'))
        ->and($attributes)->toHaveKey('detect_language', lang('igniter::system.settings.label_detect_language'))
        ->and($attributes)->toHaveKey('country_id', lang('igniter::system.settings.label_country'));
});

it('returns correct validation rules', function() {
    $rules = (new GeneralSettingsRequest)->rules();

    expect($rules['site_name'])->toContain('required', 'string', 'min:2', 'max:255')
        ->and($rules['site_email'])->toContain('required', 'email:filter', 'max:96')
        ->and($rules['site_logo'])->toContain('required', 'string')
        ->and($rules['distance_unit'])->toContain('required', 'in:mi,km')
        ->and($rules['default_geocoder'])->toContain('required', 'in:nominatim,google,chain')
        ->and($rules['maps_api_key'])->toContain('required_if:default_geocoder,google', 'alpha_dash')
        ->and($rules['timezone'])->toContain('required', 'timezone')
        ->and($rules['detect_language'])->toContain('required', 'boolean');
});
