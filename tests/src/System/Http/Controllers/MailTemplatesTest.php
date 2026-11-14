<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Classes\MailManager;
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

it('creates mail template and opens the builder', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'create']), [
            'MailTemplate' => [
                'code' => '_mail.test_template',
                'label' => 'Test Template Subject',
                'subject' => 'Test Template Subject',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk()
        ->assertSee('mail_templates\/builder\/', false);

    $template = MailTemplate::where('code', '_mail.test_template')->latest('template_id')->first();

    expect($template->is_custom)->toBeTrue()
        ->and($template->subject)->toBe('Test Template Subject');
});

it('shows the open in editor button on the edit page', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template', 'is_custom' => true]);

    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'edit/'.$mailTemplate->getKey()]))
        ->assertOk()
        ->assertSee(admin_url('mail_templates/builder/'.$mailTemplate->getKey()), false)
        ->assertSee(lang('igniter::system.mail_builder.button_open_editor'));
});

it('loads the mail template builder page', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template', 'label' => 'Builder test', 'subject' => 'Builder subject', 'is_custom' => true]);

    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'builder/'.$mailTemplate->getKey()]))
        ->assertOk()
        ->assertSee('data-control="mail-builder"', false)
        ->assertSee('data-mail-builder-subject', false)
        ->assertSee('data-mail-builder-publish', false)
        ->assertSee('name="MailTemplate[subject]"', false)
        ->assertDontSee('MailTemplate[layout_id]', false)
        ->assertDontSee('data-mail-builder-records', false)
        ->assertSee(sprintf(lang('igniter::system.mail_builder.text_title'), 'Builder test'));
});

it('flashes an error when the builder has no record', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_templates', ['slug' => 'builder']))
        ->assertSee(lang('igniter::admin.form.missing_id'));
});

it('saves builder blocks into the template', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template', 'subject' => 'Old']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'builder/'.$mailTemplate->getKey()]), [
            'MailTemplate' => [
                'subject' => 'Hello {{ $site_name }}',
                'body' => '{{ phpinfo() }}',
                'blocks' => json_encode([
                    ['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => 'Hello {{ $site_name }}', 'level' => 'h1', 'align' => 'left']],
                    ['id' => 'b2', 'code' => 'text', 'settings' => ['content' => '{{ phpinfo() }} @php echo 1; @endphp']],
                ]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $template = MailTemplate::find($mailTemplate->getKey());

    expect($template->is_custom)->toBeTrue()
        ->and($template->subject)->toBe('Hello {{ $site_name }}')
        ->and($template->blocks)->toHaveCount(2)
        ->and($template->body)->toContain('<h1 style="text-align: left">Hello {{ $site_name }}</h1>')
        ->and($template->body)->not->toContain('{{ phpinfo')
        ->and($template->body)->not->toContain('@php')
        ->and($template->body)->toContain('&#123;&#123; phpinfo() &#125;&#125;')
        ->and($template->plain_body)->not->toContain('@php');
});

it('validates the builder subject and blocks', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template', 'subject' => 'Old']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'builder/'.$mailTemplate->getKey()]), [
            'MailTemplate' => [
                'subject' => '',
                'blocks' => json_encode([['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => '']]]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(MailTemplate::find($mailTemplate->getKey())->blocks)->toBeNull();
});

it('resets a registered template to its registered content', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.test_template' => 'Test template']);
    $mailTemplate = MailTemplate::create(['code' => '_mail.test_template', 'is_custom' => true, 'blocks' => []]);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'builder/'.$mailTemplate->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertOk();

    $restored = MailTemplate::where('code', '_mail.test_template')->latest('template_id')->first();

    expect(MailTemplate::find($mailTemplate->getKey()))->toBeNull()
        ->and($restored->is_custom)->toBeFalse();
});

it('does not reset templates without registered content', function() {
    $mailTemplate = MailTemplate::create(['code' => '_mail.not_registered', 'is_custom' => true]);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_templates', ['slug' => 'builder/'.$mailTemplate->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertStatus(406);

    expect(MailTemplate::find($mailTemplate->getKey()))->not->toBeNull();
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
