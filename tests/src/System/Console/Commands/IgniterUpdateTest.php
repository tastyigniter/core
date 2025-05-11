<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\System\Classes\PackageInfo;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Console\Commands\IgniterUpdate;
use Illuminate\Console\OutputStyle;

it('checks for updates and finds none', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => null]);

    $this->artisan('igniter:update')
        ->expectsOutput('Checking for updates...')
        ->expectsOutput('0 updates found')
        ->assertExitCode(0);
});

it('checks for updates and finds some', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $packages = getPackages();
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);
    $updateManager->shouldReceive('install')->once()->andReturn($packages);
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('migrate')->once();

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating system addons: tastyigniter/core:1.0.0, item2/package:1.0.0, item2/package:1.0.0</info>')->once();
    $output->shouldReceive('writeln')->with('<info>Updating system addons complete</info>')->once();

    $command->handle();
});

it('checks for updates and runs check only and broadcasts notification', function() {
    $packages = getPackages();
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);

    $this->artisan('igniter:update --check')
        ->expectsOutput('Checking for updates...')
        ->expectsOutput('3 updates found')
        ->assertExitCode(0);
});

it('updates core only', function() {
    $packages = getPackages();
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $command->setOutput($output = mock(OutputStyle::class));

    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);
    $updateManager->shouldReceive('install')->once();
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('migrate')->once();

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnTrue();
    $output->shouldReceive('writeln')->with('<info>Updating system addons: tastyigniter/core:1.0.0</info>')->once();
    $output->shouldReceive('writeln')->with('<info>Updating system addons complete</info>')->once();

    $command->handle();
});

it('updates specified addons only', function() {
    $packages = getPackages();
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $command->setOutput($output = mock(OutputStyle::class));

    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);
    $updateManager->shouldReceive('install')->once();
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('migrate')->once();

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturn(['test.extension']);
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating system addons: item2/package:1.0.0</info>')->once();
    $output->shouldReceive('writeln')->with('<info>Updating system addons complete</info>')->once();

    $command->handle();
});

it('errors when installing update fails', function() {
    $packages = getPackages();
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating system addons: tastyigniter/core:1.0.0, item2/package:1.0.0, item2/package:1.0.0</info>')->once();
    $updateManager->shouldReceive('install')->andThrow(new Exception('Update failed'));
    $output->shouldReceive('writeln')
        ->withArgs(fn($message) => str_contains((string)$message, 'Update failed'))->once();

    $command->handle();
});

it('bails when confirm to proceed is false', function() {
    $packages = getPackages();
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(false);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => collect($packages), 'count' => 3]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $updateManager->shouldReceive('install')->never();

    $command->handle();
});

function getPackages(): array
{
    return [
        PackageInfo::fromArray([
            'code' => 'tastyigniter',
            'name' => 'TastyIgniter',
            'type' => 'core',
            'package' => 'tastyigniter/core',
            'version' => '1.0.0',
        ]),
        PackageInfo::fromArray([
            'code' => 'test.extension',
            'name' => 'Demo',
            'type' => 'extension',
            'package' => 'item2/package',
            'version' => '1.0.0',
        ]),
        PackageInfo::fromArray([
            'code' => 'test.theme',
            'name' => 'Theme',
            'type' => 'theme',
            'package' => 'item2/package',
            'version' => '1.0.0',
        ]),
    ];
}
