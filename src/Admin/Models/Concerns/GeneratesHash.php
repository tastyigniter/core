<?php

namespace Igniter\Admin\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait GeneratesHash
{
    /**
     * Generate a unique hash for this order.
     */
    public function generateHash(string $column = 'hash'): string
    {
        $hash = md5(uniqid(__CLASS__, microtime()));
        while ($this->generatesHashNewQuery()->where($column, $hash)->count() > 0) {
            $hash = md5(uniqid(__CLASS__, microtime()));
        }

        return $hash;
    }

    protected function generatesHashNewQuery(): Builder
    {
        return $this->newQuery();
    }
}