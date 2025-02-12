<?php

namespace Igniter\Tests\Flame\Database\Connections;

use Igniter\Flame\Database\Connections\Connection;
use Igniter\Flame\Database\Query\Builder as QueryBuilder;
use Illuminate\Events\Dispatcher;
use Mockery;

it('returns new query builder instance', function() {
    $connection = new Connection(Mockery::mock('PDO'));
    $queryBuilder = $connection->query();
    expect($queryBuilder)->toBeInstanceOf(QueryBuilder::class);
});

it('flushes memory cache', function() {
    expect(Connection::flushDuplicateCache())->toBeNull();
});

it('logs query with event', function() {
    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('dispatch')->once()->with('illuminate.query', ['SELECT * FROM table', [], null, null]);
    $events->shouldReceive('dispatch');
    $connection = new Connection(Mockery::mock('PDO'));
    $connection->setEventDispatcher($events);
    $connection->logQuery('SELECT * FROM table', []);
});

it('fires connection event', function() {
    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('dispatch')->twice();
    $connection = new class((Mockery::mock('PDO'))) extends Connection
    {
        public function testEvent()
        {
            $this->fireConnectionEvent('event');
        }
    };
    $connection->setEventDispatcher($events);
    $connection->testEvent();
});
