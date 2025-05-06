<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\UpdateManager;
use Igniter\System\Console\Commands\IgniterUp;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;

it('builds database tables when confirmed', function() {
    $command = mock(IgniterUp::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('confirmToProceed')->andReturn(true);
    $input = mock(InputInterface::class)->makePartial();
    $output = mock(OutputStyle::class);
    $command->setInput($input);
    $command->setOutput($output);
    $command->setLaravel(app());
    $input->shouldReceive('getOption')->with('force')->andReturnTrue();
    $input->shouldReceive('getOption')->with('database')->andReturnNull();

    $migrator = mock('migrator');
    $migrator->shouldReceive('getRepository->prepareMigrationTable')->once();
    app()->instance('migrator', $migrator);
    Schema::shouldReceive('hasColumn')->with('users', 'staff_id')->andReturnFalse();

    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('setLogsOutput')->with($output)->andReturnSelf();
    $updateManager->shouldReceive('migrate')->once();
    app()->instance(UpdateManager::class, $updateManager);

    $command->shouldReceive('call')->with('migrate', ['--force' => true, '--database' => null])->once();
    $command->shouldReceive('renameConflictingFoundationTables')->once();

    $command->handle();
});

it('does not build database tables when not confirmed', function() {
    $command = mock(IgniterUp::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('confirmToProceed')->andReturn(false);

    $command->shouldNotReceive('call');
    $command->shouldNotReceive('renameConflictingFoundationTables');

    $command->handle();
});

it('renames conflicting foundation tables', function() {
    $command = mock(IgniterUp::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $output = mock(OutputStyle::class);
    $command->setOutput($output);
    $reflection = new ReflectionClass($command);
    $property = $reflection->getProperty('components');
    $property->setAccessible(true);
    $property->setValue($command, $output);

    Schema::shouldReceive('hasColumn')->with('users', 'staff_id')->andReturn(true);
    Schema::shouldReceive('rename')->with('users', 'admin_users')->once();
    Schema::shouldReceive('hasTable')->andReturnUsing(fn($table): bool => in_array($table, ['cache', 'failed_jobs', 'jobs', 'job_batches', 'sessions']));
    Schema::shouldReceive('rename')->with(Mockery::any(), Mockery::any())->times(5);

    $output->shouldReceive('info')->times(6);

    $command->renameConflictingFoundationTables();
});

it('skips renaming if no conflicting foundation tables', function() {
    $command = mock(IgniterUp::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $output = mock(OutputStyle::class);
    $command->setOutput($output);

    Schema::shouldReceive('hasColumn')->with('users', 'staff_id')->andReturn(false);

    $output->shouldNotReceive('info');
    Schema::shouldNotReceive('rename');

    $command->renameConflictingFoundationTables();
});
