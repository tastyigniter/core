<?php

namespace Igniter\Tests\Flame\Database\Migrations;

use Igniter\Flame\Database\Connections\MySqlConnection;
use Igniter\Flame\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;
use Mockery;

it('prepares migration table when group column exists', function() {
    $connectionResolver = Mockery::mock(ConnectionResolverInterface::class);
    $schemaBuilder = Mockery::mock('stdClass');
    $connectionResolver->shouldReceive('connection')->andReturn($connection = mock(MySqlConnection::class));
    $connection->shouldReceive('getSchemaBuilder')->andReturn($schemaBuilder);
    $schemaBuilder->shouldReceive('hasColumn')->with('migrations', 'group')->andReturn(true);
    $connection->shouldReceive('table->whereNotNull->get')->andReturn(collect([
        (object)['id' => 1, 'group' => 'System', 'migration' => 'migration1'],
        (object)['id' => 2, 'group' => 'Admin', 'migration' => 'migration2'],
    ]));
    $connection->shouldReceive('table->where->update')->once()->with(['migration' => 'igniter.system::migration1']);
    $connection->shouldReceive('table->where->update')->once()->with(['migration' => 'igniter.admin::migration2']);
    $schemaBuilder->shouldReceive('dropColumns')->with('migrations', ['group']);

    $repository = new DatabaseMigrationRepository($connectionResolver, 'migrations');
    $repository->prepareMigrationTable();
});
