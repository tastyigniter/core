<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\MailTemplate;

it('loads mail templates index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates'))
        ->assertOk();
});

it('loads mail templates create page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'create']))
        ->assertOk();
});

it('loads mail templates edit page', function() {
    $mailTemplate = MailTemplate::create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]))
        ->assertOk();
});

it('loads mail templates preview page', function() {
    $mailTemplate = MailTemplate::create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]))
        ->assertOk();
});

it('creates mail template', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'create']), [
            'MailTemplate' => [
                'code' => '_mail.test_template',
                'label' => 'Test Template Subject',
                'subject' => 'Test Template Subject',
                'plain_body' => 'Test Template Text Body',
                'body' => 'Test Template HTML Body',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_templates', [
        'code' => '_mail.test_template',
        'subject' => 'Test Template Subject',
        'plain_body' => 'Test Template Text Body',
        'body' => 'Test Template HTML Body',
    ]);
});

it('updates mail template', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]), [
            'MailTemplate' => [
                'code' => '_mail.test_template',
                'label' => 'Test Template Subject',
                'subject' => 'Test Template Subject',
                'plain_body' => 'Test Template Text Body',
                'body' => 'Test Template HTML Body',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_templates', [
        'code' => '_mail.test_template',
        'subject' => 'Test Template Subject',
        'plain_body' => 'Test Template Text Body',
        'body' => 'Test Template HTML Body',
    ]);
});

it('deletes mail template', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertOk();

    $this->assertDatabaseMissing('mail_templates', [
        'template_id' => $mailTemplate->getKey(),
    ]);
});

it('tests mail template', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onTestTemplate',
        ])
        ->assertOk();
});

it('flashes error when request is invalid', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'edit/']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onTestTemplate',
        ])
        ->assertStatus(406);
});
