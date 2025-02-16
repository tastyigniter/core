<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\CurrencyRequest;

it('returns correct attribute labels', function() {
    $attributes = (new CurrencyRequest)->attributes();

    expect($attributes)->toHaveKey('currency_name', lang('igniter::system.currencies.label_title'))
        ->and($attributes)->toHaveKey('currency_code', lang('igniter::system.currencies.label_code'))
        ->and($attributes)->toHaveKey('currency_symbol', lang('igniter::system.currencies.label_symbol'))
        ->and($attributes)->toHaveKey('country_id', lang('igniter::system.currencies.label_country'))
        ->and($attributes)->toHaveKey('symbol_position', lang('igniter::system.currencies.label_symbol_position'))
        ->and($attributes)->toHaveKey('currency_rate', lang('igniter::system.currencies.label_rate'))
        ->and($attributes)->toHaveKey('thousand_sign', lang('igniter::system.currencies.label_thousand_sign'))
        ->and($attributes)->toHaveKey('decimal_sign', lang('igniter::system.currencies.label_decimal_sign'))
        ->and($attributes)->toHaveKey('decimal_position', lang('igniter::system.currencies.label_decimal_position'))
        ->and($attributes)->toHaveKey('currency_status', lang('igniter::admin.label_status'));
});

it('returns correct validation rules', function() {
    $rules = (new CurrencyRequest)->rules();

    expect($rules['currency_name'])->toContain('required', 'string', 'between:2,32')
        ->and($rules['currency_code'])->toContain('required', 'string', 'size:3')
        ->and($rules['currency_symbol'])->toContain('required', 'string')
        ->and($rules['country_id'])->toContain('required', 'integer')
        ->and($rules['symbol_position'])->toContain('string', 'size:1')
        ->and($rules['currency_rate'])->toContain('numeric')
        ->and($rules['thousand_sign'])->toContain('string', 'size:1')
        ->and($rules['decimal_sign'])->toContain('string', 'size:1')
        ->and($rules['decimal_position'])->toContain('integer', 'max:10')
        ->and($rules['currency_status'])->toContain('required', 'boolean');
});
