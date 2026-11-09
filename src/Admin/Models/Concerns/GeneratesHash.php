<?php

declare(strict_types=1);

namespace Igniter\Admin\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait GeneratesHash
{
    /**
     * Generate a unique, unguessable hash for this model.
     *
     * The hash is used as a bearer token (e.g. guest order/reservation lookups),
     * so it must come from a cryptographically secure source.
     */
    public function generateHash(string $column = 'hash'): string
    {
        do {
            $hash = $this->generateRandomHash();
        } while ($this->generatesHashNewQuery()->where($column, $hash)->exists());

        return $hash;
    }

    protected function generateRandomHash(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function generatesHashNewQuery(): Builder
    {
        return $this->newQuery();
    }
}
