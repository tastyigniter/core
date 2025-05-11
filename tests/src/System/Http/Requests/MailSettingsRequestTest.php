<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\MailSettingsRequest;

it('returns correct attribute labels', function() {
    $attributes = (new MailSettingsRequest)->attributes();

    expect($attributes)->toHaveKey('sender_name', lang('igniter::system.settings.label_sender_name'))
        ->and($attributes)->toHaveKey('sender_email', lang('igniter::system.settings.label_sender_email'))
        ->and($attributes)->toHaveKey('protocol', lang('igniter::system.settings.label_protocol'))
        ->and($attributes)->toHaveKey('mail_logo', lang('igniter::system.settings.label_mail_logo'))
        ->and($attributes)->toHaveKey('smtp_host', lang('igniter::system.settings.label_smtp_host'))
        ->and($attributes)->toHaveKey('smtp_port', lang('igniter::system.settings.label_smtp_port'))
        ->and($attributes)->toHaveKey('smtp_encryption', lang('igniter::system.settings.label_smtp_encryption'))
        ->and($attributes)->toHaveKey('smtp_user', lang('igniter::system.settings.label_smtp_user'))
        ->and($attributes)->toHaveKey('smtp_pass', lang('igniter::system.settings.label_smtp_pass'))
        ->and($attributes)->toHaveKey('mailgun_domain', lang('igniter::system.settings.label_mailgun_domain'))
        ->and($attributes)->toHaveKey('mailgun_secret', lang('igniter::system.settings.label_mailgun_secret'))
        ->and($attributes)->toHaveKey('postmark_token', lang('igniter::system.settings.label_postmark_token'))
        ->and($attributes)->toHaveKey('ses_key', lang('igniter::system.settings.label_ses_key'))
        ->and($attributes)->toHaveKey('ses_secret', lang('igniter::system.settings.label_ses_secret'))
        ->and($attributes)->toHaveKey('ses_region', lang('igniter::system.settings.label_ses_region'));
});

it('returns correct validation rules', function() {
    $rules = (new MailSettingsRequest)->rules();

    expect($rules['sender_name'])->toContain('required', 'string')
        ->and($rules['sender_email'])->toContain('required', 'email:filter')
        ->and($rules['protocol'])->toContain('required', 'string')
        ->and($rules['mail_logo'])->toContain('nullable', 'string')
        ->and($rules['smtp_host'])->toContain('required_if:protocol,smtp', 'nullable', 'string')
        ->and($rules['smtp_port'])->toContain('required_if:protocol,smtp', 'nullable', 'string')
        ->and($rules['smtp_user'])->toContain('nullable', 'string')
        ->and($rules['smtp_pass'])->toContain('nullable', 'string')
        ->and($rules['mailgun_domain'])->toContain('required_if:protocol,mailgun', 'nullable', 'string')
        ->and($rules['mailgun_secret'])->toContain('required_if:protocol,mailgun', 'nullable', 'string')
        ->and($rules['postmark_token'])->toContain('required_if:protocol,postmark', 'nullable', 'string')
        ->and($rules['ses_key'])->toContain('required_if:protocol,ses', 'nullable', 'string')
        ->and($rules['ses_secret'])->toContain('required_if:protocol,ses', 'nullable', 'string')
        ->and($rules['ses_region'])->toContain('required_if:protocol,ses', 'nullable', 'string');
});
