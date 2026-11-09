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
    $this->query->shouldReceive('where->exists')->andReturn(false);
    $hash = $this->traitModel->generateHash();

    expect($hash)->toBeString()
        ->and(strlen((string)$hash))->toBe(32)
        ->and($hash)->toMatch('/^[0-9a-f]{32}$/');
});

it('generates unpredictable hashes', function() {
    $this->query->shouldReceive('where->exists')->andReturn(false);

    $hashes = [];
    for ($i = 0; $i < 100; $i++) {
        $hashes[] = $this->traitModel->generateHash();
    }

    expect(array_unique($hashes))->toHaveCount(100);
});

it('regenerates hash if collision occurs', function() {
    $this->query->shouldReceive('where')->twice()->andReturnSelf();
    $this->query->shouldReceive('exists')->twice()->andReturn(true, false);

    $hash = $this->traitModel->generateHash();

    expect($hash)->toBeString()
        ->and(strlen((string)$hash))->toBe(32);
});

it('uses specified column to check for hash uniqueness', function() {
    $this->query->shouldReceive('where')
        ->withArgs(fn($column, $hash) => $column === 'custom_column')
        ->andReturnSelf();
    $this->query->shouldReceive('exists')->andReturn(false);

    $hash = $this->traitModel->generateHash('custom_column');

    expect($hash)->toBeString()
        ->and(strlen((string)$hash))->toBe(32);
});
