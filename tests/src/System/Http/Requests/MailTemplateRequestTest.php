<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\MailTemplateRequest;

it('returns correct attribute labels', function() {
    $attributes = (new MailTemplateRequest)->attributes();

    expect($attributes)->toHaveKey('label', lang('igniter::admin.label_description'))
        ->and($attributes)->toHaveKey('subject', lang('igniter::system.mail_templates.label_subject'))
        ->and($attributes)->toHaveKey('code', lang('igniter::system.mail_templates.label_code'))
        ->and($attributes)->toHaveKey('layout_id', lang('igniter::system.mail_templates.label_layout'));
});

it('returns correct validation rules', function() {
    $rules = (new MailTemplateRequest)->rules();

    expect($rules['label'])->toContain('required', 'string')
        ->and($rules['subject'])->toContain('required', 'string')
        ->and($rules['code'])->toContain('sometimes', 'required', 'min:2', 'max:255', 'regex:/^[a-z-_\.\:]+$/i')
        ->and($rules['code'][4]->__toString())->toBe('unique:mail_templates,code,NULL,template_id')
        ->and($rules['layout_id'])->toContain('nullable', 'integer')
        ->and($rules['plain_body'])->toContain('nullable', 'string')
        ->and($rules['body'])->toContain('string');
});
