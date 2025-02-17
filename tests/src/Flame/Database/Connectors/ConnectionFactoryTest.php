<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Connectors;

use Igniter\Flame\Database\Connections\MySqlConnection;
use Igniter\Flame\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use InvalidArgumentException;
use Mockery;
use PDOException;

beforeEach(function() {
    $this->connectionFactory = new class(app()) extends ConnectionFactory
    {
        public function testCreatePdoResolverWithHosts(array $config)
        {
            return $this->createPdoResolverWithHosts($config);
        }

        public function testCreateConnection($driver, $connection, $database, $prefix = '', array $config = [])
        {
            return $this->createConnection($driver, $connection, $database, $prefix, $config);
        }
    };
});

it('throws exception if all hosts fail', function() {
    $config = [
        'host' => ['host1', 'host2'],
        'driver' => 'mysql',
        'database' => 'database',
        'username' => 'username',
        'password' => 'password',
    ];
    $pdoResolver = $this->connectionFactory->testCreatePdoResolverWithHosts($config);
    expect(fn() => $pdoResolver())->toThrow(PDOException::class);
});

it('creates connection using resolver', function() {
    $pdo = Mockery::mock('PDO');
    Connection::resolverFor('mysql-read', function($connection, $database, $prefix, $config): MySqlConnection {
        return new MySqlConnection($connection, $database, $prefix, $config);
    });
    $connection = $this->connectionFactory->testCreateConnection('mysql-read', $pdo, 'database', 'prefix', []);
    expect($connection)->toBeInstanceOf(MySqlConnection::class);
});

it('creates mysql connection', function() {
    $pdo = Mockery::mock('PDO');
    $connection = $this->connectionFactory->testCreateConnection('mysql', $pdo, 'database', 'prefix', []);
    expect($connection)->toBeInstanceOf(MySqlConnection::class);
});

it('creates postgres connection', function() {
    $pdo = Mockery::mock('PDO');
    $connection = $this->connectionFactory->testCreateConnection('pgsql', $pdo, 'database', 'prefix', []);
    expect($connection)->toBeInstanceOf(PostgresConnection::class);
});

it('creates sqlite connection', function() {
    $pdo = Mockery::mock('PDO');
    $connection = $this->connectionFactory->testCreateConnection('sqlite', $pdo, 'database', 'prefix', []);
    expect($connection)->toBeInstanceOf(SQLiteConnection::class);
});

it('creates sqlsrv connection', function() {
    $pdo = Mockery::mock('PDO');
    $connection = $this->connectionFactory->testCreateConnection('sqlsrv', $pdo, 'database', 'prefix', []);
    expect($connection)->toBeInstanceOf(SqlServerConnection::class);
});

it('throws exception for unsupported driver', function() {
    $pdo = Mockery::mock('PDO');
    expect(fn() => $this->connectionFactory->testCreateConnection('unsupported', $pdo, 'database', 'prefix', []))
        ->toThrow(InvalidArgumentException::class);
});
