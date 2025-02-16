<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\MailPartial;

it('loads mail partials index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_partials'))
        ->assertOk();
});

it('loads mail partials create page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_partials', ['slug' => 'create']))
        ->assertOk();
});

it('loads mail partials edit page', function() {
    $mailPartial = MailPartial::create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_partials', ['slug' => 'edit/'.$mailPartial->getKey()]))
        ->assertOk();
});

it('loads mail partials preview page', function() {
    $mailPartial = MailPartial::create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_partials', ['slug' => 'edit/'.$mailPartial->getKey()]))
        ->assertOk();
});

it('creates mail partial', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'create']), [
            'MailPartial' => [
                'name' => 'Test Partial',
                'code' => 'test_partial',
                'html' => 'Test Partial Content',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_partials', [
        'name' => 'Test Partial',
        'code' => 'test_partial',
        'html' => 'Test Partial Content',
    ]);
});

it('updates mail partial', function() {
    $mailPartial = MailPartial::create(['code' => 'test_partial']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'edit/'.$mailPartial->getKey()]), [
            'MailPartial' => [
                'name' => 'Test Partial',
                'code' => 'test_partial',
                'html' => 'Test Partial Content',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_partials', [
        'name' => 'Test Partial',
        'code' => 'test_partial',
        'html' => 'Test Partial Content',
    ]);
});

it('deletes mail partial', function() {
    $mailPartial = MailPartial::create();

    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'edit/'.$mailPartial->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertOk();

    $this->assertDatabaseMissing('mail_partials', ['partial_id' => $mailPartial->getKey()]);
});
