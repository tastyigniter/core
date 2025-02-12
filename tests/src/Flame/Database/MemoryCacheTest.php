<?php

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\Status;
use Igniter\Flame\Database\MemoryCache;
use Igniter\Flame\Database\Query\Builder;

it('checks if memory cache is enabled', function() {
    /** @var Builder $query */
    $query = Status::getQuery();
    $memoryCache = new MemoryCache;

    expect($memoryCache->enabled(false))->toBe($memoryCache->enabled())
        ->and($memoryCache->put($query, ['result1', 'result2']))->toBeNull();
});

it('checks if query is cached', function() {
    /** @var Builder $query */
    $query = Status::getQuery();
    $memoryCache = new MemoryCache;

    expect($memoryCache->has($query))->toBeFalse();
});

it('retrieves cached query results', function() {
    /** @var Builder $query */
    $query = Status::getQuery();
    $memoryCache = new MemoryCache;
    expect($memoryCache->get($query))->toBeNull();

    $memoryCache->put($query, $results = ['result1', 'result2']);
    expect($memoryCache->get($query))->toBe($results);
});

it('forgets cache for a given table', function() {
    /** @var Builder $query */
    $query = Status::getQuery()->where('status_for', 'order');
    $memoryCache = new MemoryCache;
    $memoryCache->put($query, ['result1', 'result2']);

    $memoryCache->forget($query->from);
    $memoryCache->flush();
    expect($memoryCache->get($query))->toBeNull();
});
