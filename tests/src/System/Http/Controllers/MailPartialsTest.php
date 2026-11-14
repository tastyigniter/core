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

it('creates mail partial and opens the builder', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'create']), [
            'MailPartial' => [
                'name' => 'Test Partial',
                'code' => 'test_partial',
                'params' => [
                    ['name' => 'promo_code', 'label' => 'Promo code', 'sample' => 'SAVE10'],
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk()
        ->assertSee('mail_partials\/builder\/', false);

    $partial = MailPartial::where('code', 'test_partial')->latest('partial_id')->first();

    expect($partial->name)->toBe('Test Partial')
        ->and($partial->is_custom)->toBeTrue()
        ->and($partial->getParamDefinitions())->toBe([['name' => 'promo_code', 'label' => 'Promo code', 'sample' => 'SAVE10']]);
});

it('saves builder blocks into the partial using its parameters', function() {
    $mailPartial = MailPartial::create([
        'code' => 'test_partial',
        'name' => 'Test Partial',
        'params' => [['name' => 'promo_code', 'label' => 'Promo code']],
    ]);

    actingAsSuperUser()
        ->get(route('igniter.system.mail_partials', ['slug' => 'builder/'.$mailPartial->getKey()]))
        ->assertOk();

    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'builder/'.$mailPartial->getKey()]), [
            'MailPartial' => [
                'blocks' => json_encode([
                    ['id' => 'b1', 'code' => 'text', 'settings' => ['content' => 'Use {{ $promo_code }} and {{ $unknown }}']],
                    ['id' => 'b2', 'code' => 'slot', 'settings' => []],
                ]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();

    $partial = MailPartial::find($mailPartial->getKey());

    expect($partial->is_custom)->toBeTrue()
        ->and($partial->html)->toContain('Use {{ $promo_code }} and &#123;&#123; $unknown &#125;&#125;')
        ->and($partial->html)->toContain('{!! $slot !!}')
        ->and($partial->text)->toContain('{!! $slot !!}');
});

it('resets a registered partial', function() {
    $mailPartial = MailPartial::query()->firstWhere('code', 'panel') ?? MailPartial::create(['code' => 'panel', 'name' => 'Panel']);
    $mailPartial->is_custom = true;
    $mailPartial->save();

    actingAsSuperUser()
        ->post(route('igniter.system.mail_partials', ['slug' => 'builder/'.$mailPartial->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertOk();

    expect(MailPartial::find($mailPartial->getKey()))->toBeNull()
        ->and(MailPartial::firstWhere('code', 'panel'))->not->toBeNull();
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
