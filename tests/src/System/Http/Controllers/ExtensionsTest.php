<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Actions\SettingsModel;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Models\Extension;
use Igniter\Tests\Fixtures\Requests\TestRequest;
use Igniter\Tests\System\Fixtures\TestExtensionSettingsModel;
use Igniter\Tests\System\Fixtures\TestExtensionSettingsWithRulesModel;

afterEach(function() {
    SettingsModel::clearInternalCache();
});

it('loads extensions page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.extensions'))
        ->assertOk();
});

it('loads extension settings page', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([
        'igniter.tests' => getExtension(),
    ]);

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'edit/igniter/tests/settings']))
        ->assertOk();
});

it('loads extension delete page', function() {
    Extension::create([
        'name' => 'igniter.tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extension->disabled = true;
    $extension->shouldReceive('extensionMeta')->andReturn([
        'code' => 'Igniter.Tests',
        'name' => 'Igniter Tests',
        'description' => 'Test extension',
    ]);

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'delete/igniter.tests']))
        ->assertOk();
});

it('fails to load extension delete page when extension is not found', function() {
    Extension::create([
        'name' => 'igniter.tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->andReturnNull();
    $extensionManager->shouldReceive('deleteExtension')->once();

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'delete/igniter.tests']))
        ->assertRedirect();
});

it('fails to load extension delete page when extension is not disabled', function() {
    Extension::create([
        'name' => 'igniter.tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extension->disabled = false;

    actingAsSuperUser()
        ->get(route('igniter.system.extensions', ['slug' => 'delete/igniter.tests']))
        ->assertRedirect();
});

it('loads extension readme', function() {
    $extension = Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'recordId' => $extension->getKey(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadReadme',
        ])
        ->assertOk();
});

it('fails to load extension readme when request is invalid', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'recordId' => null,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadReadme',
        ])
        ->assertStatus(406);
});

it('installs extension', function() {
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extensionManager->shouldReceive('installExtension')->once()->andReturnTrue();
    $extension->shouldReceive('extensionMeta')->andReturn([
        'code' => 'Igniter.Tests',
        'name' => 'Igniter Tests',
        'description' => 'Test extension',
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => 'igniter.tests',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onInstall',
        ])
        ->assertOk();
});

it('fails to install extension when request is invalid', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => null,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onInstall',
        ])
        ->assertStatus(406);
});

it('flashes error when installation fails', function() {
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extensionManager->shouldReceive('installExtension')->once()->andReturnFalse();

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => 'igniter.tests',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onInstall',
        ]);

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_error_try_again'));
});

it('uninstalls extension', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extensionManager->shouldReceive('isRequired')->andReturnFalse();
    $extensionManager->shouldReceive('uninstallExtension')->once()->andReturnTrue();
    $extension->shouldReceive('extensionMeta')->andReturn([
        'code' => 'Igniter.Tests',
        'name' => 'Igniter Tests',
        'description' => 'Test extension',
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => 'igniter.tests',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onUninstall',
        ])
        ->assertOk();
});

it('fails to uninstall extension when request is invalid', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => null,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onUninstall',
        ])
        ->assertStatus(406);
});

it('flashes error when uninstallation fails', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extension = mock(BaseExtension::class);
    $extensionManager->shouldReceive('findExtension')->andReturn($extension);
    $extensionManager->shouldReceive('uninstallExtension')->once()->andReturnFalse();

    actingAsSuperUser()
        ->post(route('igniter.system.extensions'), [
            'code' => 'igniter.tests',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onUninstall',
        ]);

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_error_try_again'));
});

it('updates extension settings', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    TestExtensionSettingsModel::clearInternalCache();
    TestExtensionSettingsModel::set('name', 'value');
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([
        'igniter.tests' => getExtension(),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions', ['slug' => 'edit/igniter/tests/settings']), [
            'TestExtensionSettingsModel' => [
                'name' => 'value',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();
});

it('flashes error when updates extension settings fails validation', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([
        'igniter.tests' => getExtensionWithSettingsRules(),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions', ['slug' => 'edit/igniter/tests/settings']), [
            'TestExtensionSettingsWithRulesModel' => [
                'name' => 'value',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();
});

it('updates extension settings and redirects to settings page', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([
        'igniter.tests' => getExtension(),
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.extensions', ['slug' => 'edit/igniter/tests/settings']), [
            'close' => '1',
            'TestExtensionSettingsModel' => [
                'name' => 'value',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();
});

it('deletes extension', function() {
    Extension::create([
        'name' => 'Igniter.Tests',
        'status' => 1,
    ]);
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->andReturn(getExtension());
    $extensionManager->shouldReceive('deleteExtension')->once()->andReturnTrue();

    actingAsSuperUser()
        ->post(route('igniter.system.extensions', ['slug' => 'delete/igniter.tests']), [
            'recordId' => 1,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertOk();
});

it('fails to delete extension when request is invalid', function() {
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->andReturnNull();

    actingAsSuperUser()
        ->post(route('igniter.system.extensions', ['slug' => 'delete/igniter.tests']), [
            'recordId' => null,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ])
        ->assertStatus(406);
});

function getExtension()
{
    return new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'code' => 'Igniter.Tests',
                'name' => 'Igniter Tests',
                'description' => 'Test extension',
            ];
        }

        public function register() {}

        public function registerSettings(): array
        {
            return [
                'settings' => [
                    'label' => 'Test Extension Settings',
                    'icon' => 'fa fa-cog',
                    'description' => 'Manage test extension settings.',
                    'model' => TestExtensionSettingsModel::class,
                    'request' => TestRequest::class,
                    'permissions' => ['Igniter.Tests.*'],
                ],
            ];
        }
    };
}

function getExtensionWithSettingsRules()
{
    return new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'code' => 'Igniter.Tests',
                'name' => 'Igniter Tests',
                'description' => 'Test extension',
            ];
        }

        public function register() {}

        public function registerSettings(): array
        {
            return [
                'settings' => [
                    'label' => 'Test Extension Settings',
                    'icon' => 'fa fa-cog',
                    'description' => 'Manage test extension settings.',
                    'model' => TestExtensionSettingsWithRulesModel::class,
                    'permissions' => ['Igniter.Tests.*'],
                ],
            ];
        }
    };
}
