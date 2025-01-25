<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\CountryRequest;

it('returns correct attribute labels', function() {
    $attributes = (new CountryRequest)->attributes();

    expect($attributes)->toHaveKey('country_name', lang('igniter::admin.label_name'))
        ->and($attributes)->toHaveKey('priority', lang('igniter::system.countries.label_priority'))
        ->and($attributes)->toHaveKey('iso_code_2', lang('igniter::system.countries.label_iso_code2'))
        ->and($attributes)->toHaveKey('iso_code_3', lang('igniter::system.countries.label_iso_code3'))
        ->and($attributes)->toHaveKey('format', lang('igniter::system.countries.label_format'))
        ->and($attributes)->toHaveKey('status', lang('igniter::admin.label_status'));
});

it('returns correct validation rules', function() {
    $rules = (new CountryRequest)->rules();

    expect($rules['country_name'])->toContain('required', 'string', 'between:2,255')
        ->and($rules['priority'])->toContain('required', 'integer')
        ->and($rules['iso_code_2'])->toContain('required', 'string', 'size:2')
        ->and($rules['iso_code_3'])->toContain('required', 'string', 'size:3')
        ->and($rules['format'])->toContain('min:2', 'string')
        ->and($rules['status'])->toContain('required', 'boolean');
});
