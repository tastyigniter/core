<?php

namespace Igniter\Tests\System\Console\Commands;

use Composer\IO\BufferIO;
use Exception;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Exception\ComposerException;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Console\Commands\IgniterUpdate;
use Illuminate\Console\OutputStyle;

it('checks for updates and finds none', function() {
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn(['items' => null]);

    $this->artisan('igniter:update')
        ->expectsOutput('Checking for updates...')
        ->expectsOutput('No new updates found')
        ->assertExitCode(0);
});

it('checks for updates and finds some', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'tastyigniter', 'name' => 'TastyIgniter', 'type' => 'core'],
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 3,
    ]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating TastyIgniter...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>Updating extensions/themes...</info>')->once();
    $updateManager->shouldReceive('install')->twice();
    $command->shouldReceive('call')->with('igniter:up')->once();

    $command->handle();
});

it('checks for updates and runs check only and broadcasts notification', function() {
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'tastyigniter', 'name' => 'TastyIgniter', 'type' => 'core'],
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 3,
    ]);

    $this->artisan('igniter:update --check')
        ->expectsOutput('Checking for updates...')
        ->assertExitCode(0);
});

it('updates core only', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'tastyigniter', 'name' => 'TastyIgniter', 'type' => 'core'],
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 3,
    ]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnTrue();
    $output->shouldReceive('writeln')->with('<info>Updating TastyIgniter...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>Updating extensions/themes...</info>')->never();
    $updateManager->shouldReceive('install')->once();
    $command->shouldReceive('call')->with('igniter:up')->once();

    $command->handle();
});

it('updates specified addons only', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'tastyigniter', 'name' => 'TastyIgniter', 'type' => 'core'],
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 3,
    ]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>3 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturn(['igniter.demo']);
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating TastyIgniter...</info>')->never();
    $output->shouldReceive('writeln')->with('<info>Updating extensions/themes...</info>')->once();
    $updateManager->shouldReceive('install')->once();
    $command->shouldReceive('call')->with('igniter:up')->once();

    $command->handle();
});

it('errors when installing update fails', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 2,
    ]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>2 updates found</info>')->once();
    $command->shouldReceive('option')->with('addons')->andReturnNull();
    $command->shouldReceive('option')->with('core')->andReturnNull();
    $output->shouldReceive('writeln')->with('<info>Updating extensions/themes...</info>');
    $updateManager->shouldReceive('install')->andThrow(new ComposerException(new Exception('Update failed'), new BufferIO()));
    $output->shouldReceive('writeln')
        ->withArgs(fn($message) => str_contains($message, 'Error updating composer requirements: Update failed'))->once();

    $command->handle();
});

it('bails when confirm to proceed is false', function() {
    $command = mock(IgniterUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('force')->andReturn(false);
    $command->shouldReceive('option')->with('check')->andReturn(false);
    $command->shouldReceive('confirmToProceed')->andReturn(false);
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);

    $composerManager->shouldReceive('assertSchema')->once();
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestUpdateList')->andReturn([
        'items' => collect([
            (object)['code' => 'igniter.demo', 'name' => 'Demo', 'type' => 'extension'],
            (object)['code' => 'igniter.theme', 'name' => 'Theme', 'type' => 'theme'],
        ]),
        'count' => 2,
    ]);

    $output->shouldReceive('writeln')->with('<info>Checking for updates...</info>')->once();
    $output->shouldReceive('writeln')->with('<info>2 updates found</info>')->once();
    $updateManager->shouldReceive('install')->never();

    $command->handle();
});
