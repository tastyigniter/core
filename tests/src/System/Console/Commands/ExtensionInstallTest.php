<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\System\Classes\ExtensionManager;
use Illuminate\Support\Facades\Http;

it('installs an extension', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'data' => [
            [
                'code' => 'package1',
                'type' => 'extension',
                'package' => 'item2/package',
                'name' => 'Package2',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('install')->once();
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('loadExtensions')->once();
    $extensionManager->shouldReceive('installExtension')->once();

    $this->artisan('igniter:extension-install IgniterLab.Demo')
        ->expectsOutput('Installing IgniterLab.Demo extension')
        ->assertExitCode(0);
});

it('outputs error if extension not found', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'data' => [],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);

    $this->artisan('igniter:extension-install IgniterLab.Demo')
        ->expectsOutput('Extension IgniterLab.Demo not found')
        ->assertExitCode(0);
});

it('handles composer exception during installation', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'data' => [
            [
                'code' => 'package1',
                'type' => 'extension',
                'package' => 'item2/package',
                'name' => 'Package2',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('install')->andThrow(new Exception('Composer error'));

    $this->artisan('igniter:extension-install IgniterLab.Demo')
        ->expectsOutput('Composer error')
        ->assertExitCode(0);
});
