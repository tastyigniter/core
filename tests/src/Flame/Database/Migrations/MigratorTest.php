<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Migrations;

use Igniter\Flame\Database\Migrations\Migrator;
use Igniter\Flame\Filesystem\Filesystem;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;

it('runs migrations for each group', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('setGroup')->with('group1')->once();
    $repository->shouldReceive('setGroup')->with('group2')->once();

    $migrator = mock(Migrator::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $migrator->shouldReceive('getRepository')->andReturn($repository);
    $migrator->shouldReceive('write')->with(Info::class, 'Migrating group group1.')->once();
    $migrator->shouldReceive('write')->with(Info::class, 'Migrating group group2.')->once();
    $migrator->shouldReceive('run')->with('path1', [])->once();
    $migrator->shouldReceive('run')->with('path2', [])->once();

    $migrator->runGroup(['group1' => 'path1', 'group2' => 'path2']);
});

it('rolls back migrations', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('setGroup')->with('group1')->once();
    $repository->shouldReceive('getGroup')->andReturn('group1');

    $migrator = mock(Migrator::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $filesystem = mock(Filesystem::class);
    $reflection = new \ReflectionClass($migrator);
    $property = $reflection->getProperty('files');
    $property->setAccessible(true);
    $property->setValue($migrator, $filesystem);
    $migrator->shouldReceive('getRepository')->andReturn($repository);
    $migrator->shouldReceive('write')->with(Info::class, 'Rolling back group group1.')->once();
    $filesystem->shouldReceive('glob')->andReturn(['path1/1_migration.php']);
    $filesystem->shouldReceive('requireOnce');
    $filesystem->shouldReceive('getRequire')->andReturn('migration');
    $migrator->shouldReceive('rollDown')->passthru();
    $migrator->shouldReceive('runDown')->once();

    $migrator->rollbackAll(['group1' => 'path1']);
});

it('rolls back migrations for each group', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('setGroup')->with('group1')->once();
    $repository->shouldReceive('getGroup')->andReturn('group1');
    $connectionResolver = mock(ConnectionResolverInterface::class);
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('glob')->andReturn(['path1/1_migration.php']);
    $filesystem->shouldReceive('requireOnce');
    $filesystem->shouldReceive('getRequire')->andReturn('migration');

    $migrator = new Migrator($repository, $connectionResolver, $filesystem);

    expect(fn() => $migrator->rollbackAll(['group1' => ['path1']]))->toThrow('Class "group1\Database\Migrations\" not found');
});

it('it does not roll back when no migrations found', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('setGroup')->with('group1')->once();
    $connectionResolver = mock(ConnectionResolverInterface::class);
    $filesystem = mock(Filesystem::class);
    $filesystem->shouldReceive('glob')->andReturn([]);

    $migrator = new Migrator($repository, $connectionResolver, $filesystem);
    $migrator->rollbackAll(['group1' => ['path1']]);
});

it('resets migrations for each group', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('setGroup')->with('group1')->once();
    $repository->shouldReceive('setGroup')->with('group2')->once();

    $migrator = mock(Migrator::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $migrator->shouldReceive('getRepository')->andReturn($repository);
    $migrator->shouldReceive('write')->with(Info::class, 'Resetting group group1.')->once();
    $migrator->shouldReceive('write')->with(Info::class, 'Resetting group group2.')->once();
    $migrator->shouldReceive('reset')->with(['path1'], false)->once();
    $migrator->shouldReceive('reset')->with(['path2'], false)->once();

    $migrator->resetAll(['group1' => 'path1', 'group2' => 'path2']);
});

it('returns migration name with group prefix', function() {
    $repository = mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('getGroup')->andReturn('group1', 'group1', null, null);
    $migrator = mock(Migrator::class)->makePartial();
    $migrator->shouldReceive('getRepository')->andReturn($repository);

    $migrationName = $migrator->getMigrationName('path/to/migration.php');
    expect($migrationName)->toBe('group1::migration');

    $migrationName = $migrator->getMigrationName('path/to/migration2.php');
    expect($migrationName)->toBe('migration2');
});

it('returns migration class name with group namespace', function() {
    $migrator = resolve('migrator');
    $migrator->getRepository()->setGroup('Author.Extension');

    expect(fn() => $migrator->resolve('2023_01_01_000000_create_users_table'))
        ->toThrow('Class "Author\\Extension\\Database\\Migrations\\CreateUsersTable" not found');
});
