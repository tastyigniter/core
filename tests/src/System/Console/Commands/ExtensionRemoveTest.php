<?php

namespace Igniter\Tests\System\Console\Commands;

use Composer\IO\BufferIO;
use Exception;
use Igniter\Flame\Exception\ComposerException;
use Igniter\System\Classes\ExtensionManager;

it('removes extension successfully', function() {
    $extensionManager = mock(ExtensionManager::class);
    $extensionManager->shouldReceive('getIdentifier')->with('igniterlab.demo')->andReturn('IgniterLab.Demo');
    $extensionManager->shouldReceive('hasExtension')->with('IgniterLab.Demo')->andReturn(true);
    $extensionManager->shouldReceive('deleteExtension')->with('IgniterLab.Demo')->once();
    app()->instance(ExtensionManager::class, $extensionManager);

    $this->artisan('igniter:extension-remove igniterlab.demo')
        ->expectsOutput('Removing extension: IgniterLab.Demo')
        ->expectsOutput('Deleted extension: IgniterLab.Demo')
        ->assertExitCode(0);
});

it('outputs error if extension not found', function() {
    $extensionManager = mock(ExtensionManager::class);
    $extensionManager->shouldReceive('getIdentifier')->with('igniterlab.demo')->andReturn('IgniterLab.Demo');
    $extensionManager->shouldReceive('hasExtension')->with('IgniterLab.Demo')->andReturn(false);
    app()->instance(ExtensionManager::class, $extensionManager);

    $this->artisan('igniter:extension-remove igniterlab.demo')
        ->expectsOutput('Unable to find a registered extension called "IgniterLab.Demo"')
        ->assertExitCode(0);
});

it('does not proceed if confirmation is denied', function() {
    $this->app['env'] = 'production';
    $extensionManager = mock(ExtensionManager::class);
    $extensionManager->shouldReceive('getIdentifier')->with('igniterlab.demo')->andReturn('IgniterLab.Demo');
    $extensionManager->shouldReceive('hasExtension')->with('IgniterLab.Demo')->andReturn(true);
    app()->instance(ExtensionManager::class, $extensionManager);

    $this->artisan('igniter:extension-remove igniterlab.demo --no-interaction')
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);
});

it('handles composer exception during removal', function() {
    $extensionManager = mock(ExtensionManager::class);
    $extensionManager->shouldReceive('getIdentifier')->with('igniterlab.demo')->andReturn('IgniterLab.Demo');
    $extensionManager->shouldReceive('hasExtension')->with('IgniterLab.Demo')->andReturn(true);
    $extensionManager->shouldReceive('deleteExtension')->with('IgniterLab.Demo')
        ->andThrow(new ComposerException(new Exception('Composer error'), new BufferIO()));
    app()->instance(ExtensionManager::class, $extensionManager);

    $this->artisan('igniter:extension-remove igniterlab.demo')
        ->expectsOutput("Error updating composer requirements: Composer error\nOutput: ")
        ->assertExitCode(0);
});
