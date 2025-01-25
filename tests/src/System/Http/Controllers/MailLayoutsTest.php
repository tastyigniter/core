<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\MailLayout;

it('loads mail layouts index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_layouts'))
        ->assertOk();
});

it('loads mail layouts create page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.mail_layouts', ['slug' => 'create']))
        ->assertOk();
});

it('loads mail layouts edit page', function() {
    $mailLayout = MailLayout::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_layouts', ['slug' => 'edit/'.$mailLayout->getKey()]))
        ->assertOk();
});

it('loads mail layouts preview page', function() {
    $mailLayout = MailLayout::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.mail_layouts', ['slug' => 'edit/'.$mailLayout->getKey()]))
        ->assertOk();
});

it('creates mail layout', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'create']), [
            'MailLayout' => [
                'name' => 'Test Layout',
                'code' => 'test_layout',
                'layout' => 'Test Layout Content',
                'language_id' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_layouts', [
        'name' => 'Test Layout',
        'code' => 'test_layout',
        'layout' => 'Test Layout Content',
    ]);
});

it('updates mail layout', function() {
    $mailLayout = MailLayout::factory()->create(['code' => 'default']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'edit/'.$mailLayout->getKey()]), [
            'MailLayout' => [
                'name' => 'Updated Layout',
                'code' => 'updated_layout',
                'layout' => 'Updated Body',
                'language_id' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $this->assertDatabaseHas('mail_layouts', [
        'name' => 'Updated Layout',
        'code' => $mailLayout->code,
        'layout' => 'Updated Body',
    ]);
});

it('deletes mail layout', function() {
    $mailLayout = MailLayout::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'edit/'.$mailLayout->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertOk();

    expect(MailLayout::find($mailLayout->getKey()))->toBeNull();
});
