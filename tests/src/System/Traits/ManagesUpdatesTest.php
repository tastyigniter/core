<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Exception;
use Igniter\System\Classes\UpdateManager;

it('searches extensions successfully', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('searchItems')->with('extension', 'igniter.api')->andReturn([
        [
            'name' => 'Igniter.Api',
            'code' => 'igniter.api',
            'description' => 'description',
        ],
    ]);

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => ['search' => 'igniter.api']]))
        ->assertOk()
        ->assertSee('Igniter.Api');
});

it('returns error when search fails', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('searchItems')->andThrow(new Exception('Search failed'));

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => ['search' => 'igniter.api']]))
        ->assertOk()
        ->assertSee('Search failed');
});

it('throws exception when no selected recommended items', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyRecommended',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_no_items'));
});

it('applies recommended extensions correctly', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestApplyItems')->andReturn(collect([
        'data' => [
            ['code' => 'igniter.test'],
            ['code' => 'igniter.anothertest'],
        ],
    ]));

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'install_items' => ['igniter.test', 'igniter.anothertest'],
            'items' => [
                [
                    'name' => 'igniter.test',
                    'type' => 'extension',
                    'ver' => '1.0.0',
                    'action' => 'install',
                ],
                [
                    'name' => 'igniter.anothertest',
                    'type' => 'theme',
                    'ver' => '1.0.0',
                    'action' => 'install',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyRecommended',
        ])
        ->assertStatus(200)
        ->assertJson([
            'steps' => [
                'check' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'check',
                    'progress' => 'Performing pre installation checks...',
                ],
                'install' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'install',
                    'progress' => 'Updating composer requirements...',
                ],
                'complete' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'complete',
                    'progress' => 'Finishing installation...',
                ],
            ]]);
});

it('returns process steps when install items are applied', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestApplyItems')->andReturn(collect([
        'data' => [
            ['code' => 'igniter.test'],
            ['code' => 'igniter.anothertest'],
        ],
    ]));

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'items' => [
                [
                    'name' => 'igniter.test',
                    'type' => 'extension',
                    'ver' => '1.0.0',
                    'action' => 'install',
                ],
                [
                    'name' => 'igniter.anothertest',
                    'type' => 'theme',
                    'ver' => '1.0.0',
                    'action' => 'install',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(200)
        ->assertJson([
            'steps' => [
                'check' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'check',
                    'progress' => 'Performing pre installation checks...',
                ],
                'install' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'install',
                    'progress' => 'Updating composer requirements...',
                ],
                'complete' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'complete',
                    'progress' => 'Finishing installation...',
                ],
            ]]);
});

it('throws exception when no selected items to install', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_no_items'));
});

it('throws exception when no items to install', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestApplyItems')->andReturn(collect());

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'items' => [
                [
                    'name' => 'igniter.test',
                    'type' => 'extension',
                    'ver' => '1.0.0',
                    'action' => 'install',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(406)
        ->assertSee(lang('igniter::system.updates.alert_no_items'));
});

it('returns process steps when update items are applied', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            ['code' => 'igniter.test'],
            ['code' => 'igniter.anothertest'],
        ]),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyUpdate',
        ])
        ->assertStatus(200)
        ->assertJson([
            'steps' => [
                'check' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'check',
                    'progress' => 'Performing pre installation checks...',
                ],
                'install' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'install',
                    'progress' => 'Updating composer requirements...',
                ],
                'complete' => [
                    'meta' => [
                        ['code' => 'igniter.test'],
                        ['code' => 'igniter.anothertest'],
                    ],
                    'process' => 'complete',
                    'progress' => 'Finishing installation...',
                ],
            ]]);
});

it('returns process steps when update core items are applied', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            ['code' => 'TastyIgniter', 'type' => 'core'],
            ['code' => 'igniter.test', 'type' => 'extension'],
        ]),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyUpdate',
        ])
        ->assertStatus(200)
        ->assertJson([
            'steps' => [
                'check' => [
                    'meta' => [
                        ['code' => 'TastyIgniter', 'type' => 'core'],
                    ],
                    'process' => 'check',
                    'progress' => 'Performing pre installation checks...',
                ],
                'install' => [
                    'meta' => [
                        ['code' => 'TastyIgniter', 'type' => 'core'],
                    ],
                    'process' => 'install',
                    'progress' => 'Updating composer requirements...',
                ],
                'complete' => [
                    'meta' => [
                        ['code' => 'TastyIgniter', 'type' => 'core'],
                    ],
                    'process' => 'complete',
                    'progress' => 'Finishing installation...',
                ],
            ]]);
});

it('throws exception when no items to update', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
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
    $updateManager->shouldReceive('applySiteDetail')->with('carte_key');
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

it('returns process steps when processing items', function($process) {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('getLogs')->andReturn([]);
    $updateManager->shouldReceive('preInstall');
    $updateManager->shouldReceive('install');
    $updateManager->shouldReceive('completeInstall');

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'process' => $process,
            'meta' => [
                [
                    'code' => 'igniter.test',
                    'name' => 'Test Extension',
                    'package' => 'igniter/test',
                    'author' => 'Igniter Labs',
                    'type' => 'extension',
                    'version' => '1.0.0',
                    'hash' => 'hash',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onProcessItems',
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
})->with([
    ['check'],
    ['install'],
    ['complete'],
]);

it('throws exception when composer install fails', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('getLogs')->andReturn([]);
    $updateManager->shouldReceive('install')->andThrow(new Exception('Composer install failed'));

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'process' => 'install',
            'meta' => [
                [
                    'code' => 'igniter.test',
                    'name' => 'Test Extension',
                    'package' => 'igniter/test',
                    'author' => 'Igniter Labs',
                    'type' => 'extension',
                    'version' => '1.0.0',
                    'hash' => 'hash',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onProcessItems',
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => false,
            'message' => 'Composer install failed'."\n\n".'<a href="https://tastyigniter.com/support/articles/failed-updates" target="_blank">Troubleshoot</a>'."\n\n",
        ]);
});
