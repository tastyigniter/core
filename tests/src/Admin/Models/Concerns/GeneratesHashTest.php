<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Models\Concerns;

use Igniter\Admin\Models\Concerns\GeneratesHash;
use Igniter\Flame\Database\Builder;

beforeEach(function() {
    $this->query = mock(Builder::class);
    $this->traitModel = new class($this->query)
    {
        use GeneratesHash;

        public function __construct(protected $query) {}

        protected function newQuery()
        {
            return $this->query;
        }
    };
});

it('generates a unique hash', function() {
    $this->query->shouldReceive('where->count')->andReturn(0);
    $hash = $this->traitModel->generateHash();

    expect($hash)->toBeString()
        ->and(strlen($hash))->toBe(32);
});

it('regenerates hash if collision occurs', function() {
    $this->query->shouldReceive('where->count')->andReturn(1, 0);

    $hash = $this->traitModel->generateHash();

    expect($hash)->toBeString()
        ->and(strlen($hash))->toBe(32);
});

it('uses specified column to check for hash uniqueness', function() {
    $this->query->shouldReceive('where')
        ->withArgs(fn($column, $hash) => $column === 'custom_column')
        ->andReturnSelf();
    $this->query->shouldReceive('count')->andReturn(0);

    $hash = $this->traitModel->generateHash('custom_column');

    expect($hash)->toBeString()
        ->and(strlen($hash))->toBe(32);
});
