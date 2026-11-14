<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\MailPartialRequest;

it('returns correct attribute labels', function() {
    $attributes = (new MailPartialRequest)->attributes();

    expect($attributes)->toHaveKey('name', lang('igniter::admin.label_name'))
        ->and($attributes)->toHaveKey('code', lang('igniter::system.mail_templates.label_code'))
        ->and($attributes)->toHaveKey('params.*.name', lang('igniter::system.mail_builder.label_param_name'));
});

it('returns correct validation rules', function() {
    $rules = (new MailPartialRequest)->rules();

    expect($rules['name'])->toContain('required', 'string')
        ->and($rules['code'])->toContain('sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i')
        ->and($rules['code'][3]->__toString())->toBe('unique:mail_partials,NULL,NULL,partial_id')
        ->and($rules['params'])->toContain('nullable', 'array')
        ->and($rules['params.*.name'])->toContain('required', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/', 'not_in:slot,body')
        ->and($rules)->not->toHaveKeys(['html', 'text']);
});
