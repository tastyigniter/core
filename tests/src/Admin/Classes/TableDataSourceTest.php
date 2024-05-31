<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\TableDataSource;

beforeEach(function() {
    $this->tableDataSource = new TableDataSource();
    $this->tableDataSource->initRecords(['record1', 'record2', 'record3']);
});

it('initializes records correctly', function() {
    expect($this->tableDataSource->getCount())->toBe(3);
});

it('returns count correctly', function() {
    expect($this->tableDataSource->getCount())->toBe(3);
});

it('purges records correctly', function() {
    $this->tableDataSource->purge();

    expect($this->tableDataSource->getCount())->toBe(0);
});

it('gets records correctly', function() {
    expect($this->tableDataSource->getRecords(1, 1))->toBe(['record2']);
});

it('gets all records correctly', function() {
    expect($this->tableDataSource->getAllRecords())->toBe(['record1', 'record2', 'record3']);
});

it('resets correctly', function() {
    $this->tableDataSource->readRecords(2);
    $this->tableDataSource->reset();

    expect($this->tableDataSource->readRecords(1))->toBe(['record1']);
});

it('reads records correctly', function() {
    expect($this->tableDataSource->readRecords(2))->toBe(['record1', 'record2'])
        ->and($this->tableDataSource->readRecords(2))->toBe(['record3']);
});
