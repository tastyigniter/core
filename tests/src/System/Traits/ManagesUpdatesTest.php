<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Exception;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\System\Classes\HubManager;
use Igniter\System\Classes\UpdateManager;

it('searches extensions successfully', function() {
    app()->instance(HubManager::class, $hubManager = mock(HubManager::class));
    $hubManager->shouldReceive('listItems')->andReturn([
        'data' => [
            [
                'name' => 'Igniter.Api',
                'code' => 'igniter.api',
                'description' => 'description',
            ],
        ],
    ]);
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('getInstalledItems')->andReturn([]);

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => ['search' => 'igniter.api']]))
        ->assertOk()
        ->assertSee('Igniter.Api');
});

it('returns error when search fails', function() {
    app()->instance(HubManager::class, $hubManager = mock(HubManager::class));
    $hubManager->shouldReceive('listItems')->andThrow(new Exception('Search failed'));
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('getInstalledItems')->andReturn([]);

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => ['search' => 'igniter.api']]))
        ->assertOk()
        ->assertSee('Search failed');
});

it('returns successful message when install items are applied', function() {
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('hasValidCarte')->andReturnTrue();
    $updateManager->shouldReceive('install')->withArgs(function($items, $callback): true {
        $callback('out', 'Composer installing');

        return true;
    })->andReturn([
        'data' => [
            ['code' => 'igniter.test'],
            ['code' => 'igniter.anothertest'],
        ],
    ]);
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('getLogs')->once()->andReturn([]);
    $updateManager->shouldReceive('migrate')->once();
    app()->instance(ComposerManager::class, $composerManager = mock(ComposerManager::class));
    $composerManager->shouldReceive('getPackageName')->andReturn('test/package');

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'item' => [
                'code' => 'igniter.test',
                'package' => 'igniter/test',
                'name' => 'Test Extension',
                'type' => 'extension',
                'version' => '1.0.0',
                'action' => 'install',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Installing system addons...<br><br>Installing system addons complete<i class="fa fa-check fa-fw"></i><br>Running database migrations...<br>Running database migrations complete<i class="fa fa-check fa-fw"></i><br><b>See system logs for more details</b>',
            'success' => true,
            'redirect' => admin_url('extensions'),
        ]);
});

it('throws exception when composer install fails', function() {
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('hasValidCarte')->andReturnTrue();
    $updateManager->shouldReceive('install')->andThrow(new Exception('Composer install failed'));
    app()->instance(ComposerManager::class, $composerManager = mock(ComposerManager::class));
    $composerManager->shouldReceive('getPackageName')->andReturn('test/package');

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'item' => [
                'code' => 'igniter.test',
                'package' => 'igniter/test',
                'name' => 'Test Extension',
                'type' => 'extension',
                'version' => '1.0.0',
                'action' => 'install',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(200)
        ->assertJson([
            'message' => implode('<br>', [
                'Installing system addons...',
                nl2br(
                    'Composer install failed'."\n\n"
                    .'<a href="https://tastyigniter.com/support/articles/failed-updates" target="_blank">Troubleshoot</a>'
                    ."\n\n",
                ),
                '<b>See system logs for more details</b>',
            ]),
            'success' => false,
            'redirect' => null,
        ]);
});

it('throws exception when no selected items to install', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(406);
});

it('throws exception when no items to install', function() {
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('hasValidCarte')->andReturnTrue();
    $updateManager->shouldReceive('requestApplyItems')->andReturn(collect());

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'item' => [
                'code' => 'igniter.test',
                'name' => 'Test Extension',
                'type' => 'extension',
                'ver' => '1.0.0',
                'action' => 'install',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(406);
});

it('returns successful message when update items are applied', function() {
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('hasValidCarte')->andReturnTrue();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            ['code' => 'igniter.test'],
            ['code' => 'igniter.anothertest'],
        ]),
    ]);
    $updateManager->shouldReceive('install')->once();
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('getLogs')->once()->andReturn([]);
    $updateManager->shouldReceive('migrate')->once();

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyUpdate',
        ])
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Updating system addons...<br><br>Updating system addons complete<i class="fa fa-check fa-fw"></i><br>Running database migrations...<br>Running database migrations complete<i class="fa fa-check fa-fw"></i><br><b>See system logs for more details</b>',
            'success' => true,
            'redirect' => admin_url('updates'),
        ]);
});

it('throws exception when no items to update', function() {
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('hasValidCarte')->andReturnTrue();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect(),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyUpdate',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_item_to_update'));
});

it('redirects after checking updates', function() {
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('requestUpdateList')->with(true);
    app()->instance(UpdateManager::class, $updateManager);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onCheckUpdates',
        ])
        ->assertStatus(200)
        ->assertSee('X_IGNITER_REDIRECT');
});

it('throws exception when no item code to ignore', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onIgnoreUpdate',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_item_to_ignore'));
});

it('redirects after ignoring update', function() {
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('markedAsIgnored')->with('item_code', true);
    app()->instance(UpdateManager::class, $updateManager);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => 'item_code',
            'remove' => 1,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onIgnoreUpdate',
        ])
        ->assertStatus(200)
        ->assertSee('X_IGNITER_REDIRECT');
});

it('throws exception when no carte key is specified', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyCarte',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_no_carte_key'));
});

it('redirects after applying carte key', function() {
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('applyCarte')->with('carte_key');
    app()->instance(UpdateManager::class, $updateManager);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'carte_key' => 'carte_key',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyCarte',
        ])
        ->assertStatus(200)
        ->assertSee('X_IGNITER_REDIRECT');
});

it('redirects after clearing carte key', function() {
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('clearCarte')->once();
    app()->instance(UpdateManager::class, $updateManager);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'carte_key' => 'carte_key',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onClearCarte',
        ])
        ->assertStatus(200)
        ->assertSee('X_IGNITER_REDIRECT');
});
