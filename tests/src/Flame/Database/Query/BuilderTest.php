<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Query;

use Igniter\Admin\Models\Status;
use Illuminate\Support\Facades\Cache;

it('caches query results', function() {
    Cache::shouldReceive('remember')
        ->withArgs(function($cacheKey, $minutes, $callback): true {
            $callback();

            return true;
        })
        ->andReturn(collect($expected = ['result1', 'result2']));

    $builder = Status::getQuery()->cacheTags(null)->remember(now()->addMinutes(10));

    expect($builder->get()->all())->toBe($expected);
});

it('caches query results forever', function() {
    Cache::shouldReceive('rememberForever')
        ->withArgs(function($cacheKey, $callback): bool {
            return $cacheKey === 'cache_key' && $callback();
        })
        ->andReturn(collect($expected = ['result1', 'result2']));

    $builder = Status::getQuery()->rememberForever('cache_key');

    expect($builder->get()->all())->toBe($expected);
});

it('retrieves query results when duplicate caching is enabled', function() {
    $builder = Status::getQuery()->enableDuplicateCache()->remember(10, 'cache_key');
    $builder->flushDuplicateCache();
    expect($builder->get())->toEqual($builder->get());
    $builder->disableDuplicateCache();
});

it('lists attributes in key value pair', function() {
    expect(Status::getQuery()->lists('status_name', 'status_id'))->not()->toBeEmpty();
});
