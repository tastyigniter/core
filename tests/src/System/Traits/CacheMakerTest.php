<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Igniter\System\Traits\CacheMaker;
use Illuminate\Support\Facades\Cache;

function makeCacheMaker(): object
{
    return new class
    {
        use CacheMaker { CacheMaker::getCacheKey as parentGetCacheKey; }

        protected function getCacheKey(): string
        {
            $this->parentGetCacheKey();

            return 'class_id';
        }
    };
}

it('retrieves value from cache with key', function() {
    Cache::expects('get')->with('class_id.key', null)->andReturn('value')->once();

    $result = makeCacheMaker()->getCache('key');
    expect($result)->toBe('value');
});

it('retrieves default value when key not in cache', function() {
    Cache::expects('get')->with('class_id.nonexistent_key', 'default_value')->andReturn('default_value')->once();

    $result = makeCacheMaker()->getCache('nonexistent_key', 'default_value');
    expect($result)->toBe('default_value');
});

it('saves key value pair in cache', function() {
    Cache::expects('put')->with('class_id.key', 'value')->once();

    makeCacheMaker()->putCache('key', 'value');
});

it('checks if cache has key', function() {
    Cache::expects('has')->with('class_id.key')->andReturnTrue()->once();

    $result = makeCacheMaker()->hasCache('key');
    expect($result)->toBeTrue();
});

it('forgets key from cache', function() {
    Cache::expects('forget')->with('class_id.key')->once();
    makeCacheMaker()->forgetCache('key');
});

it('resets cache', function() {
    Cache::expects('forget')->with('class_id')->once();
    makeCacheMaker()->resetCache();
});
