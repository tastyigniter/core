<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\AdvancedSettingsRequest;

it('returns correct attribute labels', function() {
    $request = new AdvancedSettingsRequest;

    $attributes = $request->attributes();

    expect($attributes)->toHaveKey('enable_request_log', lang('igniter::system.settings.label_enable_request_log'))
        ->and($attributes)->toHaveKey('maintenance_mode', lang('igniter::system.settings.label_maintenance_mode'))
        ->and($attributes)->toHaveKey('maintenance_message', lang('igniter::system.settings.label_maintenance_message'))
        ->and($attributes)->toHaveKey('activity_log_timeout', lang('igniter::system.settings.label_activity_log_timeout'));
});

it('returns correct validation rules', function() {
    $request = new AdvancedSettingsRequest;

    $rules = $request->rules();

    expect($rules['enable_request_log'])->toContain('required', 'boolean')
        ->and($rules['maintenance_mode'])->toContain('required', 'boolean')
        ->and($rules['maintenance_message'])->toContain('required_if:maintenance_mode,1', 'string')
        ->and($rules['activity_log_timeout'])->toContain('required', 'integer', 'max:999');
});
