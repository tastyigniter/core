<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\MailLayoutRequest;

it('returns correct attribute labels', function() {
    $attributes = (new MailLayoutRequest)->attributes();

    expect($attributes)->toHaveKey('name', lang('igniter::admin.label_name'))
        ->and($attributes)->toHaveKey('code', lang('igniter::system.mail_templates.label_code'))
        ->and($attributes)->toHaveKey('layout_css', lang('igniter::system.mail_templates.label_layout_css'));
});

it('returns correct validation rules', function() {
    $rules = (new MailLayoutRequest)->rules();

    expect($rules['name'])->toContain('required', 'string', 'between:2,32')
        ->and($rules['code'])->toContain('sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i')
        ->and($rules['code'][3]->__toString())->toBe('unique:mail_layouts,NULL,NULL,layout_id')
        ->and($rules['language_id'])->toContain('integer')
        ->and($rules['layout_css'])->toContain('nullable', 'string', 'not_regex:/<|@import/i')
        ->and($rules)->not->toHaveKeys(['layout', 'plain_layout']);
});
