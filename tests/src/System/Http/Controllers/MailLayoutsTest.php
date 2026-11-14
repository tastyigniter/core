<?php

declare(strict_types=1);

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

it('creates mail layout and opens the builder', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'create']), [
            'MailLayout' => [
                'name' => 'Test Layout',
                'code' => 'test_layout',
                'layout_css' => '.footer { color: red; }',
                'language_id' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk()
        ->assertSee('mail_layouts\/builder\/', false);

    $layout = MailLayout::where('code', 'test_layout')->latest('layout_id')->first();

    expect($layout->name)->toBe('Test Layout')
        ->and($layout->is_locked)->toBeTrue()
        ->and($layout->layout_css)->toBe('.footer { color: red; }');
});

it('saves builder blocks into the layout', function() {
    $mailLayout = MailLayout::factory()->create(['code' => 'builder_layout', 'is_locked' => true]);

    actingAsSuperUser()
        ->get(route('igniter.system.mail_layouts', ['slug' => 'builder/'.$mailLayout->getKey()]))
        ->assertOk()
        ->assertSee('data-control="mail-builder"', false);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'builder/'.$mailLayout->getKey()]), [
            'MailLayout' => [
                'blocks' => json_encode([
                    ['id' => 'b1', 'code' => 'header', 'settings' => ['text' => 'Updated Header', 'show_logo' => false]],
                ]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $layout = MailLayout::find($mailLayout->getKey());

    expect($layout->is_locked)->toBeTrue()
        ->and($layout->layout)->toContain('Updated Header')
        // The required content slot is added when missing
        ->and($layout->layout)->toContain('{!! $body !!}')
        ->and(array_column($layout->blocks, 'code'))->toBe(['header', 'content_slot']);
});

it('resets a registered layout', function() {
    $mailLayout = MailLayout::query()->firstWhere('code', 'default') ?? MailLayout::factory()->create(['code' => 'default']);
    $mailLayout->is_locked = true;
    $mailLayout->blocks = [];
    $mailLayout->save();

    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'builder/'.$mailLayout->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertOk();

    expect(MailLayout::find($mailLayout->getKey()))->toBeNull()
        ->and(MailLayout::firstWhere('code', 'default'))->not->toBeNull();
});

it('rejects layout css that opens a tag', function() {
    $mailLayout = MailLayout::factory()->create(['code' => 'default']);

    actingAsSuperUser()
        ->post(route('igniter.system.mail_layouts', ['slug' => 'edit/'.$mailLayout->getKey()]), [
            'MailLayout' => [
                'name' => 'Updated Layout',
                'layout_css' => '</style><script>alert(1)</script>',
                'language_id' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(MailLayout::find($mailLayout->getKey())->layout_css)->not->toContain('<script>');
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
