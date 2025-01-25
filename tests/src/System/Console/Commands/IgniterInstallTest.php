<?php

namespace Igniter\Tests\System\Console\Commands;

use Facades\Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Console\Commands\IgniterInstall;
use Igniter\User\Models\User;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Mockery\MockInterface;

function setupInstallation(): IgniterInstall|MockInterface
{
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('assertSchema')->once();
    File::shouldReceive('exists')->withArgs(fn($path) => ends_with($path, '/.env'))->andReturn(false, true, true);
    File::shouldReceive('exists')->withArgs(fn($path) => ends_with($path, '/example.env'))->andReturnTrue();
    File::shouldReceive('move')->withArgs(fn($from, $to) => ends_with($from, '/.env') && ends_with($to, '/backup.env'));
    File::shouldReceive('delete')->withArgs(fn($path) => ends_with($path, '/.env'));
    File::shouldReceive('copy')->withArgs(fn($from, $to) => ends_with($from, '/example.env') && ends_with($to, '/.env'));
    SystemHelper::shouldReceive('replaceInEnv');
    config([
        'app.key' => null,
        'app.url' => 'http://localhost',
        'database.connections.mysql.host' => 'localhost',
        'database.connections.mysql.port' => '3306',
        'database.connections.mysql.database' => 'igniter',
        'database.connections.mysql.username' => 'root',
        'database.connections.mysql.password' => '',
        'database.connections.mysql.prefix' => '',
    ]);
    DB::partialMock()->shouldReceive('purge')->once();
    Igniter::shouldReceive('hasDatabase')->andReturnFalse();
    $installCommand = mock(IgniterInstall::class)->makePartial();
    $installCommand->setOutput($output = mock(OutputStyle::class));
    $installCommand->shouldReceive('option')->andReturnFalse();
    $installCommand->shouldReceive('confirm')->andReturnTrue();
    $installCommand->shouldReceive('alert')->with('INSTALLATION STARTED')->once();
    $installCommand->shouldReceive('line')->byDefault();
    $installCommand->shouldReceive('ask')->with('MySQL Host', 'localhost')->andReturn('localhost');
    $installCommand->shouldReceive('ask')->with('MySQL Port', '3306')->andReturn('3306');
    $installCommand->shouldReceive('ask')->with('MySQL Database', 'igniter')->andReturn('igniter');
    $installCommand->shouldReceive('ask')->with('MySQL Username', 'root')->andReturn('root');
    $installCommand->shouldReceive('ask')->with('MySQL Password', '')->andReturn('');
    $installCommand->shouldReceive('ask')->with('MySQL Table Prefix', '')->andReturn('');
    $installCommand->shouldReceive('ask')->with('Site Name', 'TastyIgniter')->andReturn('TastyIgniter');
    $installCommand->shouldReceive('ask')->with('Site URL', 'http://localhost')->andReturn('http://localhost');
    $installCommand->shouldReceive('confirm')->withSomeOfArgs('Install demo data?')->andReturn(true);
    $installCommand->shouldReceive('call')->with('igniter:up', ['--force' => true]);
    $installCommand->shouldReceive('ask')->with('Admin Name', 'Chef Admin')->andReturn('Chef Admin');
    $output->shouldReceive('ask')->withArgs(function($name, $value, $callback) {
        return $name === 'Admin Email' && $callback($value);
    })->andReturn('admin@domain.tld');
    $output->shouldReceive('ask')->withArgs(function($name, $value, $callback) {
        return $name === 'Admin Password' && $callback($value);
    })->andReturn('123456');
    $output->shouldReceive('ask')->withArgs(function($name, $value, $callback) {
        return $name === 'Admin Username' && $callback($value);
    })->andReturn('admin');
    $installCommand->shouldReceive('line')->with('Admin user admin created!');
    $installCommand->shouldReceive('call')->with('storage:link')->once();
    $installCommand->shouldReceive('call')->with('igniter:theme-vendor-publish');
    $installCommand->shouldReceive('alert')->with('INSTALLATION COMPLETE')->once();

    return $installCommand;
}

it('installs TastyIgniter successfully', function() {
    Event::fake();
    $installCommand = setupInstallation();
    Process::shouldReceive('run')->andReturn(0);
    Igniter::shouldReceive('adminUri')->andReturn('admin');
    SystemHelper::shouldReceive('runningOnMac')->andReturnTrue();

    $installCommand->handle();

    expect(User::where('email', 'admin@domain.tld')->exists())->toBeTrue();
});

it('skips setup if already installed and not forced', function() {
    Event::fake();
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('assertSchema')->once();
    Igniter::shouldReceive('hasDatabase')->andReturnTrue();
    $installCommand = mock(IgniterInstall::class)->makePartial();
    $installCommand->setOutput($output = mock(OutputStyle::class));
    $installCommand->shouldReceive('option')->andReturnFalse();
    $installCommand->shouldReceive('confirm')->with('Application appears to be installed already. Continue anyway?', false)->andReturnFalse();
    $installCommand->shouldReceive('alert')->with('INSTALLATION STARTED')->never();

    $installCommand->handle();
});

it('opens browser window when installing in windows', function() {
    Event::fake();
    $installCommand = setupInstallation();
    Process::shouldReceive('run')->andReturn(0);
    Igniter::shouldReceive('adminUri')->andReturn('admin');
    SystemHelper::shouldReceive('runningOnMac')->andReturnFalse();
    SystemHelper::shouldReceive('runningOnWindows')->andReturnTrue();

    $installCommand->handle();

    expect(User::where('email', 'admin@domain.tld')->exists())->toBeTrue();
});

it('opens browser window when installing in linux', function() {
    Event::fake();
    $installCommand = setupInstallation();
    Process::shouldReceive('run')->andReturn(0);
    Igniter::shouldReceive('adminUri')->andReturn('admin');
    SystemHelper::shouldReceive('runningOnMac')->andReturnFalse();
    SystemHelper::shouldReceive('runningOnWindows')->andReturnFalse();

    $installCommand->handle();

    expect(User::where('email', 'admin@domain.tld')->exists())->toBeTrue();
});
